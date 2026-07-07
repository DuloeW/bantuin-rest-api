<?php

namespace App\Service\Transaction;

use App\Models\Transaction;
use App\Models\TransactionRevision;
use App\Models\ReportTransaction;
use App\Models\Refund;
use App\Models\Review;
use App\Traits\ServiceResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    use ServiceResponse;

    /**
     * Complete a transaction by a helper.
     *
     * @param string $transactionId
     * @param string $helperId
     * @param array $data
     * @param array $uploadedImages
     * @return array
     */
    public function completeTransaction(string $transactionId, string $helperId, array $data, array $uploadedImages): array
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($transactionId, $helperId, $data, $uploadedImages, &$uploadedPaths) {
                // Lock transaction to prevent race conditions
                $transaction = Transaction::whereKey($transactionId)->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => ['Transaksi tidak ditemukan.']
                    ]);
                }

                // Check authorization
                if ($transaction->helper_id !== $helperId) {
                    throw ValidationException::withMessages([
                        'helper' => ['Hanya helper yang ditugaskan yang dapat menyelesaikan transaksi ini.']
                    ]);
                }

                // Check transaction status (must be on_progress to be completed)
                if ($transaction->status !== 'on_progress') {
                    throw ValidationException::withMessages([
                        'status' => ['Transaksi hanya dapat diselesaikan jika berstatus "on_progress". Status saat ini: ' . $transaction->status]
                    ]);
                }

                // Update transaction fields
                $transaction->update([
                    'status' => 'completed',
                    'completion_notes' => $data['completion_notes'],
                    'finished_at' => now(),
                ]);

                // Store uploaded proof images
                foreach ($uploadedImages as $imageFile) {
                    $path = $imageFile->store('transactions/completion', 'public');
                    $uploadedPaths[] = $path;

                    $transaction->images()->create([
                        'url' => $path,
                        'file_name' => $imageFile->getClientOriginalName(),
                        'file_type' => $imageFile->getClientMimeType(),
                        'type' => 'completion',
                    ]);
                }

                // Reload relations and return updated transaction
                $transaction->load(['completionImages', 'helper', 'requester', 'offer']);

                return $this->successPayload($transaction, 'Transaksi berhasil diselesaikan.');
            });
        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            // Delete uploaded files on transaction failure
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Get all transactions for a user (either as requester or helper).
     *
     * @param string $userId
     * @param string|null $status
     * @return array
     */
    public function getUsersTransactions(string $userId, ?string $status = null): array
    {
        $query = Transaction::with(['completionImages', 'helper', 'requester', 'offer.post.images', 'offer.post.category', 'payment', 'escrow'])
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('helper_id', $userId);
            });

        if ($status) {
            $query->where('status', $status);
        }

        $transactions = $query->latest()->get();

        return $this->successPayload($transactions, 'Transaksi berhasil diambil.');
    }

    /**
     * Get active/running transactions for a user.
     */
    public function getActiveTransactions(string $userId): array
    {
        $transactions = Transaction::with(['completionImages', 'helper', 'requester', 'offer.post.images', 'offer.post.category', 'payment', 'escrow'])
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('helper_id', $userId);
            })
            ->whereNotIn('status', ['completed', 'refunded', 'cancelled', 'failed'])
            ->latest()
            ->get();

        return $this->successPayload($transactions, 'Transaksi aktif berhasil diambil.');
    }

    /**
     * Approve transaction and release escrow to the helper.
     */
    public function approveTransaction(string $transactionId, string $requesterId): array
    {
        try {
            $transaction = DB::transaction(function () use ($transactionId, $requesterId) {
                $transaction = Transaction::whereKey($transactionId)->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => ['Transaksi tidak ditemukan.']
                    ]);
                }

                if ($transaction->requester_id !== $requesterId) {
                    throw ValidationException::withMessages([
                        'requester' => ['Hanya requester yang dapat menyetujui transaksi ini.']
                    ]);
                }

                if ($transaction->status !== 'on_progress') {
                    throw ValidationException::withMessages([
                        'status' => ['Transaksi hanya dapat disetujui jika berstatus "on_progress". Status saat ini: ' . $transaction->status]
                    ]);
                }

                if ($transaction->completion_notes === null) {
                    throw ValidationException::withMessages([
                        'work' => ['Helper belum mengirimkan bukti/laporan hasil pekerjaan.']
                    ]);
                }

                // Update transaction
                $transaction->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                ]);

                // Update escrow
                $escrow = $transaction->escrow;
                if ($escrow) {
                    $escrow->update([
                        'status' => 'released',
                        'released_at' => now(),
                        'release_notes' => 'Pekerjaan diterima dan disetujui',
                    ]);

                    // Add funds to helper's wallet balance
                    $helper = $transaction->helper;
                    if ($helper) {
                        $helper->increment('wallet_balance', $escrow->net_amount);
                    }
                }

                return $transaction;
            });

            // Trigger auto-payout directly to Helper's bank account (outside of DB transaction to avoid lock holding)
            try {
                $this->disburseToHelper($transaction->id);
            } catch (\Exception $e) {
                Log::error('Auto-payout failed for transaction ' . $transaction->id . ': ' . $e->getMessage());
            }

            return $this->successPayload($transaction->load(['helper', 'requester', 'escrow']), 'Transaksi disetujui dan dana dilepas ke helper.');

        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Request a revision for the helper's work.
     */
    public function requestRevision(string $transactionId, string $requesterId, array $data): array
    {
        try {
            return DB::transaction(function () use ($transactionId, $requesterId, $data) {
                $transaction = Transaction::whereKey($transactionId)->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => ['Transaksi tidak ditemukan.']
                    ]);
                }

                if ($transaction->requester_id !== $requesterId) {
                    throw ValidationException::withMessages([
                        'requester' => ['Hanya requester yang dapat meminta revisi untuk transaksi ini.']
                    ]);
                }

                if ($transaction->status !== 'on_progress') {
                    throw ValidationException::withMessages([
                        'status' => ['Revisi hanya dapat diminta jika transaksi berstatus "on_progress". Status saat ini: ' . $transaction->status]
                    ]);
                }

                if ($transaction->completion_notes === null) {
                    throw ValidationException::withMessages([
                        'work' => ['Helper belum mengirimkan bukti/laporan hasil pekerjaan, revisi tidak dapat diminta.']
                    ]);
                }

                // Check pending revisions
                $hasPendingRevision = $transaction->revisions()->where('status', 'pending')->exists();
                if ($hasPendingRevision) {
                    throw ValidationException::withMessages([
                        'revision' => ['Masih ada revisi aktif yang belum diselesaikan oleh helper.']
                    ]);
                }

                // Check max revision limit
                $revisionCount = $transaction->revisions()->count();
                if ($revisionCount >= $transaction->max_revision) {
                    throw ValidationException::withMessages([
                        'revision' => ['Batas maksimal revisi (' . $transaction->max_revision . ' kali) telah tercapai. Jika masih bermasalah, silakan ajukan laporan sengketa.']
                    ]);
                }

                // Create revision
                $revision = $transaction->revisions()->create([
                    'revision_notes' => $data['revision_notes'],
                    'status' => 'pending',
                ]);

                return $this->successPayload($revision, 'Permintaan revisi berhasil dikirim.');
            });
        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Respond to a revision request (Accept & Fix OR Reject).
     */
    public function respondToRevision(string $revisionId, string $helperId, array $data, array $uploadedImages = []): array
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($revisionId, $helperId, $data, $uploadedImages, &$uploadedPaths) {
                $revision = TransactionRevision::whereKey($revisionId)->lockForUpdate()->first();

                if (!$revision) {
                    throw ValidationException::withMessages([
                        'revision' => ['Data revisi tidak ditemukan.']
                    ]);
                }

                $transaction = $revision->transaction;

                if (!$transaction || $transaction->helper_id !== $helperId) {
                    throw ValidationException::withMessages([
                        'helper' => ['Hanya helper yang ditugaskan yang dapat merespons revisi ini.']
                    ]);
                }

                if ($revision->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' => ['Revisi sudah diproses sebelumnya. Status saat ini: ' . $revision->status]
                    ]);
                }

                $action = $data['action'];

                if ($action === 'fixed') {
                    // Update revision status
                    $revision->update([
                        'status' => 'fixed',
                        'completed_at' => now(),
                    ]);

                    // Update transaction completion notes
                    $transaction->update([
                        'completion_notes' => $data['completion_notes'],
                    ]);

                    // Store uploaded proof images
                    foreach ($uploadedImages as $imageFile) {
                        $path = $imageFile->store('transactions/completion', 'public');
                        $uploadedPaths[] = $path;

                        $transaction->images()->create([
                            'url' => $path,
                            'file_name' => $imageFile->getClientOriginalName(),
                            'file_type' => $imageFile->getClientMimeType(),
                            'type' => 'completion',
                        ]);
                    }

                    $transaction->load(['completionImages', 'revisions']);
                    return $this->successPayload($transaction, 'Laporan revisi berhasil dikirim ke requester.');
                } else {
                    // action === 'rejected'
                    // Update revision status
                    $revision->update([
                        'status' => 'rejected',
                    ]);

                    // Update transaction status
                    $transaction->update([
                        'status' => 'disputed',
                    ]);

                    // Update escrow
                    $escrow = $transaction->escrow;
                    if ($escrow) {
                        $escrow->update([
                            'status' => 'disputed',
                            'dispute_reason' => $data['dispute_reason'],
                        ]);
                    }

                    // Create report transaction
                    $report = ReportTransaction::create([
                        'transaction_id' => $transaction->id,
                        'reporter_id' => $helperId,
                        'reported_id' => $transaction->requester_id,
                        'reason_category' => 'Revisi Ditolak oleh Helper',
                        'description' => $data['dispute_reason'],
                        'status' => 'pending',
                    ]);

                    return $this->successPayload($report, 'Revisi ditolak. Transaksi dialihkan ke status sengketa (disputed) untuk dimediasi admin.');
                }
            });
        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Request a refund for a transaction by the requester.
     */
    public function requestRefund(string $transactionId, string $requesterId, array $data): array
    {
        try {
            return DB::transaction(function () use ($transactionId, $requesterId, $data) {
                $transaction = Transaction::whereKey($transactionId)->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => ['Transaksi tidak ditemukan.']
                    ]);
                }

                if ($transaction->requester_id !== $requesterId) {
                    throw ValidationException::withMessages([
                        'requester' => ['Hanya requester yang dapat mengajukan refund untuk transaksi ini.']
                    ]);
                }

                $escrow = $transaction->escrow;
                if (!$escrow || $escrow->status !== 'held') {
                    throw ValidationException::withMessages([
                        'escrow' => ['Refund hanya dapat diajukan jika dana masih ditahan di escrow. Status escrow saat ini: ' . ($escrow ? $escrow->status : 'tidak ada')]
                    ]);
                }

                // Check for existing pending/processing refund
                $hasActiveRefund = Refund::where('transaction_id', $transaction->id)
                    ->whereIn('status', ['pending', 'processing'])
                    ->exists();

                if ($hasActiveRefund) {
                    throw ValidationException::withMessages([
                        'refund' => ['Pengajuan refund aktif untuk transaksi ini sudah ada dan sedang diproses.']
                    ]);
                }

                // Create refund request
                $refund = Refund::create([
                    'transaction_id' => $transaction->id,
                    'payment_id' => $transaction->payment->id ?? $escrow->payment_id,
                    'user_id' => $requesterId,
                    'amount' => $escrow->held_amount,
                    'reason' => $data['reason'],
                    'status' => 'pending',
                    'gateway_refund_id' => 'REF-' . strtoupper(uniqid()),
                ]);

                return $this->successPayload($refund, 'Pengajuan refund berhasil dikirim. Menunggu persetujuan helper.');
            });
        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Respond to a refund request by the helper (Approve or Reject).
     */
    public function respondToRefund(string $refundId, string $helperId, array $data): array
    {
        try {
            return DB::transaction(function () use ($refundId, $helperId, $data) {
                $refund = Refund::whereKey($refundId)->lockForUpdate()->first();

                if (!$refund) {
                    throw ValidationException::withMessages([
                        'refund' => ['Data pengajuan refund tidak ditemukan.']
                    ]);
                }

                if ($refund->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' => ['Pengajuan refund ini sudah diproses sebelumnya. Status saat ini: ' . $refund->status]
                    ]);
                }

                $transaction = $refund->transaction;
                if (!$transaction || $transaction->helper_id !== $helperId) {
                    throw ValidationException::withMessages([
                        'helper' => ['Hanya helper yang ditugaskan pada transaksi ini yang dapat merespons pengajuan refund.']
                    ]);
                }

                $action = $data['action'];

                if ($action === 'approved') {
                    // Update refund record
                    $refund->update([
                        'status' => 'completed',
                        'processed_at' => now(),
                    ]);

                    // Update escrow
                    $escrow = $transaction->escrow;
                    if ($escrow) {
                        $escrow->update([
                            'status' => 'refunded',
                            'refunded_at' => now(),
                        ]);
                    }

                    // Update transaction status
                    $transaction->update([
                        'status' => 'cancelled',
                    ]);

                    return $this->successPayload($refund->load('transaction'), 'Permintaan refund disetujui. Dana akan dikembalikan ke requester.');
                } else {
                    // action === 'rejected'
                    // Update refund record
                    $refund->update([
                        'status' => 'rejected',
                    ]);

                    // Update transaction status to disputed
                    $transaction->update([
                        'status' => 'disputed',
                    ]);

                    // Update escrow status to disputed
                    $escrow = $transaction->escrow;
                    if ($escrow) {
                        $escrow->update([
                            'status' => 'disputed',
                            'dispute_reason' => $data['dispute_reason'] ?? 'Refund ditolak oleh Helper',
                        ]);
                    }

                    // Create report/dispute record for admin mediation
                    $report = ReportTransaction::create([
                        'transaction_id' => $transaction->id,
                        'reporter_id' => $transaction->requester_id,
                        'reported_id' => $helperId,
                        'reason_category' => 'Refund Ditolak oleh Helper',
                        'description' => $data['dispute_reason'] ?? 'Helper menolak pengajuan refund dari requester.',
                        'status' => 'pending',
                    ]);

                    return $this->successPayload($report, 'Refund ditolak. Transaksi dialihkan ke status sengketa (disputed) untuk dimediasi admin.');
                }
            });
        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Get all reviewed transactions for a user.
     *
     * @param string $userId
     * @return array
     */
    public function getReviewedTransactions(string $userId): array
    {
        try {
            $transactions = Transaction::with([
                'completionImages',
                'helper.photoProfile',
                'requester.photoProfile',
                'offer.post.category',
                'offer.post.images',
                'reviews.reviewer.photoProfile',
                'reviews.reviewed.photoProfile',
            ])
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('helper_id', $userId);
            })
            ->whereHas('reviews')
            ->latest()
            ->get();

            return $this->successPayload($transactions, 'Riwayat transaksi yang sudah di-review berhasil diambil.');
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Create a review for a transaction.
     *
     * @param string $transactionId
     * @param string $reviewerId
     * @param array $data
     * @param array $uploadedImages
     * @return array
     */
    public function createReview(string $transactionId, string $reviewerId, array $data, array $uploadedImages = []): array
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($transactionId, $reviewerId, $data, $uploadedImages, &$uploadedPaths) {
                // Lock transaction
                $transaction = Transaction::whereKey($transactionId)->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => ['Transaksi tidak ditemukan.']
                    ]);
                }

                // Check transaction status (must be completed to be reviewed)
                if ($transaction->status !== 'completed') {
                    throw ValidationException::withMessages([
                        'status' => ['Review hanya dapat diberikan jika transaksi sudah berstatus "completed". Status saat ini: ' . $transaction->status]
                    ]);
                }

                // Check if user is requester or helper
                $isRequester = $transaction->requester_id === $reviewerId;
                $isHelper = $transaction->helper_id === $reviewerId;

                if (!$isRequester && !$isHelper) {
                    throw ValidationException::withMessages([
                        'user' => ['Hanya requester atau helper yang bersangkutan yang dapat me-review transaksi ini.']
                    ]);
                }

                // Check if user has already reviewed this transaction
                $alreadyReviewed = Review::where('transaction_id', $transactionId)
                    ->where('reviewer_id', $reviewerId)
                    ->exists();

                if ($alreadyReviewed) {
                    throw ValidationException::withMessages([
                        'review' => ['Anda sudah memberikan ulasan untuk transaksi ini.']
                    ]);
                }

                // Determine who is reviewed
                $reviewedId = $isRequester ? $transaction->helper_id : $transaction->requester_id;

                // Create review
                $review = Review::create([
                    'transaction_id' => $transactionId,
                    'reviewer_id' => $reviewerId,
                    'reviewed_id' => $reviewedId,
                    'rating' => $data['rating'],
                    'comment' => $data['comment'] ?? null,
                ]);

                // Store uploaded review images
                foreach ($uploadedImages as $imageFile) {
                    $path = $imageFile->store('reviews', 'public');
                    $uploadedPaths[] = $path;

                    $review->images()->create([
                        'url' => $path,
                        'file_name' => $imageFile->getClientOriginalName(),
                        'file_type' => $imageFile->getClientMimeType(),
                        'type' => 'review',
                    ]);
                }

                $review->load(['reviewer.photoProfile', 'reviewed.photoProfile', 'images']);

                return $this->successPayload($review, 'Ulasan berhasil dikirim.', 201);
            });
        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            // Delete uploaded files on failure
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Disburse funds directly to Helper's primary bank account using Midtrans Iris.
     *
     * @param string $transactionId
     * @return array
     */
    public function disburseToHelper(string $transactionId): array
    {
        try {
            $transaction = Transaction::with(['helper.primaryBankAccount', 'escrow'])->find($transactionId);
            if (!$transaction) {
                throw new Exception('Transaksi tidak ditemukan.');
            }

            $helper = $transaction->helper;
            if (!$helper) {
                throw new Exception('Helper tidak ditemukan untuk transaksi ini.');
            }

            // Get helper's primary bank account
            $bankAccount = $helper->primaryBankAccount;
            if (!$bankAccount) {
                throw ValidationException::withMessages([
                    'bank_account' => ['Helper belum mendaftarkan rekening bank utama untuk pencairan otomatis.']
                ]);
            }

            $escrow = $transaction->escrow;
            if (!$escrow) {
                throw new Exception('Data escrow tidak ditemukan untuk transaksi ini.');
            }

            $netAmount = $escrow->net_amount;

            $isProduction = config('midtrans.is_production', false);
            $apiKey = config('midtrans.iris_api_key') ?? config('midtrans.server_key');
            $baseUrl = $isProduction 
                ? 'https://app.midtrans.com/iris/api/v1' 
                : 'https://app.sandbox.midtrans.com/iris/api/v1';

            $idempotencyKey = 'payout-' . $transaction->id;

            $response = Http::withHeaders([
                'X-Idempotency-Key' => $idempotencyKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->withBasicAuth($apiKey, '')
            ->post($baseUrl . '/payouts', [
                'payouts' => [
                    [
                        'beneficiary_name' => $bankAccount->account_name,
                        'beneficiary_account' => $bankAccount->account_number,
                        'beneficiary_bank' => $bankAccount->bank_code,
                        'amount' => number_format($netAmount, 2, '.', ''),
                        'notes' => 'Pencairan BANTUIN untuk transaksi ' . substr($transaction->id, 0, 8),
                    ]
                ]
            ]);

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error_message'] ?? 'Midtrans Iris API returned status code ' . $response->status();
                throw new Exception('Gagal melakukan transfer ke bank Helper via Midtrans: ' . $errorMessage);
            }

            $resJson = $response->json();
            $payoutInfo = $resJson['payouts'][0] ?? [];
            $referenceNo = $payoutInfo['reference_no'] ?? 'N/A';
            $payoutStatus = $payoutInfo['status'] ?? 'pending';

            // Update escrow release notes with disbursement details
            $escrow->update([
                'release_notes' => trim($escrow->release_notes . "\nTransfer otomatis Midtrans Iris berhasil. Status: " . $payoutStatus . ", Ref: " . $referenceNo),
            ]);

            return $this->successPayload([
                'transaction_id' => $transaction->id,
                'reference_no' => $referenceNo,
                'status' => $payoutStatus,
                'amount' => $netAmount,
                'bank' => $bankAccount->bank_name,
                'account_number' => $bankAccount->account_number,
            ], 'Dana berhasil ditransfer ke rekening bank Helper.');

        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Update a transaction's status, completion notes, finished_at, and completion images.
     *
     * @param string $transactionId
     * @param array $data
     * @param array|null $uploadedImages
     * @return array
     */
    public function updateTransaction(string $transactionId, array $data, ?array $uploadedImages = []): array
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($transactionId, $data, $uploadedImages, &$uploadedPaths) {
                // Lock transaction to prevent race conditions
                $transaction = Transaction::whereKey($transactionId)->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => ['Transaksi tidak ditemukan.']
                    ]);
                }

                $updateData = [];
                if (array_key_exists('status', $data)) {
                    $updateData['status'] = $data['status'];
                }
                if (array_key_exists('completion_notes', $data)) {
                    $updateData['completion_notes'] = $data['completion_notes'];
                }
                if (array_key_exists('finished_at', $data)) {
                    $updateData['finished_at'] = $data['finished_at'];
                }

                if (!empty($updateData)) {
                    $transaction->update($updateData);
                }

                // Replace completion images if new ones are uploaded
                if (!empty($uploadedImages)) {
                    foreach ($transaction->completionImages as $oldImage) {
                        Storage::disk('public')->delete($oldImage->url);
                        $oldImage->delete();
                    }

                    foreach ($uploadedImages as $imageFile) {
                        $path = $imageFile->store('transactions/completion', 'public');
                        $uploadedPaths[] = $path;

                        $transaction->images()->create([
                            'url' => $path,
                            'file_name' => $imageFile->getClientOriginalName(),
                            'file_type' => $imageFile->getClientMimeType(),
                            'type' => 'completion',
                        ]);
                    }
                }

                // Reload relations and return updated transaction
                $transaction->load(['completionImages', 'helper', 'requester', 'offer']);

                return $this->successPayload($transaction, 'Transaksi berhasil diperbarui.');
            });
        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            // Delete uploaded files on transaction failure
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Cancel a transaction manually.
     *
     * @param string $transactionId
     * @param string $userId
     * @return array
     */
    public function cancelTransaction(string $transactionId, string $userId): array
    {
        try {
            return DB::transaction(function () use ($transactionId, $userId) {
                // Lock transaction to prevent race conditions
                $transaction = Transaction::whereKey($transactionId)->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => ['Transaksi tidak ditemukan.']
                    ]);
                }

                // Check authorization (only requester or helper can cancel)
                if ($transaction->requester_id !== $userId && $transaction->helper_id !== $userId) {
                    throw ValidationException::withMessages([
                        'authorization' => ['Anda tidak memiliki otorisasi untuk membatalkan transaksi ini.']
                    ]);
                }

                // Only allow cancellation if status is pending
                if ($transaction->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' => ['Transaksi hanya dapat dibatalkan jika berstatus "pending". Status saat ini: ' . $transaction->status]
                    ]);
                }

                // Update transaction status to cancelled
                $transaction->update([
                    'status' => 'cancelled',
                ]);

                // Update payment status if exists
                if ($transaction->payment) {
                    $transaction->payment->update([
                        'status' => 'failed',
                    ]);
                }

                return $this->successPayload($transaction, 'Transaksi berhasil dibatalkan.');
            });
        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }
}

