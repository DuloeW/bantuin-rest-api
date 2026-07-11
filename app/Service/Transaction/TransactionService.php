<?php

namespace App\Service\Transaction;

use App\Enum\ActiveOffEnum;
use App\Enum\OfferingStatusEnum;
use App\Jobs\AutoApproveTransaction;
use App\Models\Refund;
use App\Models\ReportTransaction;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\TransactionRevision;
use App\Service\Notification\NotificationService;
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
                        'transaction' => ['Transaction not found.']
                    ]);
                }

                // Check authorization
                if ($transaction->helper_id !== $helperId) {
                    throw ValidationException::withMessages([
                        'helper' => ['Only the assigned helper can complete this transaction.']
                    ]);
                }

                // Check transaction status (must be on_progress to be completed)
                if ($transaction->status !== 'on_progress') {
                    throw ValidationException::withMessages([
                        'status' => ['Transaction can only be completed when status is "on_progress". Current status: ' . $transaction->status]
                    ]);
                }

                // Update transaction fields
                $transaction->update([
                    'status' => 'pending_approval',
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

                // Dispatch auto-approval job delayed by 1 minute
                AutoApproveTransaction::dispatch($transaction->id)
                    ->delay(now()->addHour(24))
                    ->afterCommit();

                if ($transaction->requester) {
                    try {
                        $projectTitle = $transaction->offer->post->title ?? 'Project';
                        $providerName = $transaction->helper->first_name ?? 'Provider';

                        app(NotificationService::class)->sendToUser(
                            $transaction->requester,
                            'Work Submitted for Review',
                            $providerName . ' has submitted the final work for "' . $projectTitle . '". Please review and confirm within the time limit.',
                            [
                                'transaction_id' => (string) $transaction->id,
                                'offer_id'       => (string) $transaction->offer_id,
                                'screen'         => 'offer_list',
                            ],
                            'work_submission'
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to send work submission notification: ' . $e->getMessage());
                    }
                }

                return $this->successPayload($transaction, 'Transaction completed successfully.');
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
        $query = Transaction::with(['completionImages', 'helper', 'requester', 'offer.post.images', 'offer.post.category', 'payment', 'escrow', 'reviews'])
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('helper_id', $userId);
            });

        if ($status) {
            $query->where('status', $status);
        }

        $transactions = $query->latest()->get();

        return $this->successPayload($transactions, 'Transactions retrieved successfully.');
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

        return $this->successPayload($transactions, 'Active transactions retrieved successfully.');
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
                        'transaction' => ['Transaction not found.']
                    ]);
                }

                if ($transaction->requester_id !== $requesterId) {
                    throw ValidationException::withMessages([
                        'requester' => ['Only the requester can approve this transaction.']
                    ]);
                }

                if ($transaction->status !== 'pending_approval') {
                    throw ValidationException::withMessages([
                        'status' => ['Transaction can only be approved when status is "pending_approval". Current status: ' . $transaction->status]
                    ]);
                }

                if ($transaction->completion_notes === null) {
                    throw ValidationException::withMessages([
                        'work' => ['The helper has not yet submitted proof/report of completed work.']
                    ]);
                }

                // Update transaction
                $transaction->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                ]);

                // Update associated offer to completed, and set post detail to active if post type is offer
                $offer = $transaction->offer;
                if ($offer) {
                    $offer->update([
                        'status' => OfferingStatusEnum::COMPLETED->value,
                    ]);

                    $post = $offer->post;
                    if ($post && $post->type === 'offer') {
                        if ($post->offerDetail) {
                            $post->offerDetail->update([
                                'status' => ActiveOffEnum::ACTIVE->value,
                            ]);
                        }
                    }
                }

                // Update escrow
                $escrow = $transaction->escrow;
                if ($escrow) {
                    $escrow->update([
                        'status' => 'released',
                        'released_at' => now(),
                        'release_notes' => 'Work accepted and approved',
                    ]);

                    // Add funds to helper's wallet balance
                    $helper = $transaction->helper;
                    if ($helper) {
                        $helper->increment('wallet_balance', $escrow->net_amount);
                    }
                }

                return $transaction;
            });

            // Auto-payout has been removed. Funds now only increase the wallet_balance,
            // and the helper must manually request a withdrawal via the Withdrawal API.

            if ($transaction->helper) {
                try {
                    $projectTitle = $transaction->offer->post->title ?? 'Project';

                    app(NotificationService::class)->sendToUser(
                        $transaction->helper,
                        'Work Accepted!',
                        'Your work submission for "' . $projectTitle . '" has been accepted by the requester. Thank you for your service!',
                        [
                            'transaction_id' => (string) $transaction->id,
                            'offer_id'       => (string) $transaction->offer_id,
                            'screen'         => 'offer_list',
                        ],
                        'work_accepted'
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to send work accepted notification: ' . $e->getMessage());
                }
            }

            return $this->successPayload($transaction->load(['helper', 'requester', 'escrow']), 'Transaction approved and funds released to helper.');

        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Request a revision for the helper's work.
     */
    public function requestRevision(string $transactionId, string $requesterId, array $data, array $uploadedImages = []): array
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($transactionId, $requesterId, $data, $uploadedImages, &$uploadedPaths) {
                $transaction = Transaction::whereKey($transactionId)->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => ['Transaction not found.']
                    ]);
                }

                if ($transaction->requester_id !== $requesterId) {
                    throw ValidationException::withMessages([
                        'requester' => ['Only the requester can request a revision for this transaction.']
                    ]);
                }

                if ($transaction->status !== 'pending_approval') {
                    throw ValidationException::withMessages([
                        'status' => ['Revision can only be requested when the transaction is in "pending_approval" status. Current status: ' . $transaction->status]
                    ]);
                }

                // Block revision if a refund is already pending
                if ($transaction->status === 'pending_refund') {
                    throw ValidationException::withMessages([
                        'status' => ['A refund request is already in progress for this transaction. You cannot request a revision at this time.']
                    ]);
                }

                if ($transaction->completion_notes === null) {
                    throw ValidationException::withMessages([
                        'work' => ['The helper has not submitted proof of work yet, revision cannot be requested.']
                    ]);
                }

                // Check pending revisions
                $hasPendingRevision = $transaction->revisions()->where('status', 'pending')->exists();
                if ($hasPendingRevision) {
                    throw ValidationException::withMessages([
                        'revision' => ['There is still an active revision that has not been resolved by the helper.']
                    ]);
                }

                // Check max revision limit
                $revisionCount = $transaction->revisions()->count();
                if ($revisionCount >= $transaction->max_revision) {
                    throw ValidationException::withMessages([
                        'revision' => ['Maximum revision limit (' . $transaction->max_revision . ' times) has been reached. If issues persist, please file a dispute report.']
                    ]);
                }

                // Create revision
                $revision = $transaction->revisions()->create([
                    'revision_notes' => $data['revision_notes'],
                    'status' => 'pending',
                ]);

                // Store uploaded revision images
                foreach ($uploadedImages as $imageFile) {
                    $path = $imageFile->store('transactions/revisions', 'public');
                    $uploadedPaths[] = $path;

                    $revision->images()->create([
                        'url' => $path,
                        'file_name' => $imageFile->getClientOriginalName(),
                        'file_type' => $imageFile->getClientMimeType(),
                        'type' => 'tr-revision',
                    ]);
                }

                // Update transaction status to pending_revision for helper to re-work
                $transaction->update([
                    'status' => 'pending_revision',
                ]);

                if ($transaction->helper) {
                    try {
                        $projectTitle = $transaction->offer->post->title ?? 'Project';

                        app(NotificationService::class)->sendToUser(
                            $transaction->helper,
                            'Revision Requested',
                            'The requester has requested a revision for "' . $projectTitle . '". Please check the feedback and update your work.',
                            [
                                'transaction_id' => (string) $transaction->id,
                                'offer_id'       => (string) $transaction->offer_id,
                                'screen'         => 'offer_list',
                            ],
                            'revision_requested'
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to send revision requested notification: ' . $e->getMessage());
                    }
                }

                return $this->successPayload($revision->load('images'), 'Revision request sent successfully.');
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
                        'revision' => ['Revision data not found.']
                    ]);
                }

                $transaction = $revision->transaction;

                if (!$transaction || $transaction->helper_id !== $helperId) {
                    throw ValidationException::withMessages([
                        'helper' => ['Only the assigned helper can respond to this revision.']
                    ]);
                }

                if ($revision->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' => ['This revision has already been processed. Current status: ' . $revision->status]
                    ]);
                }

                $action = $data['action'];

                if ($action === 'accepted') {
                    // Helper accept revision → status transaksi menjadi 'revision'
                    $revision->update([
                        'revision_deadline' => $data['revision_deadline'] ?? null,
                    ]);

                    $transaction->update([
                        'status' => 'revision',
                    ]);

                    $transaction->load(['completionImages', 'revisions', 'revisions.images']);

                    return $this->successPayload($transaction, 'Revision accepted. Please re-do the work before the deadline.');
                }

                if ($action === 'fixed') {
                    // Update revision status
                    $revision->update([
                        'status' => 'fixed',
                        'completed_at' => now(),
                    ]);

                    // Update transaction completion notes and set status to pending_approval
                    $transaction->update([
                        'status' => 'pending_approval',
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

                    $transaction->load(['completionImages', 'revisions', 'revisions.images']);

                    AutoApproveTransaction::dispatch($transaction->id)
                        ->delay(now()->addHours(24))
                        ->afterCommit();

                    return $this->successPayload($transaction, 'Revision report submitted to requester successfully.');
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

                    if ($transaction->offer) {
                        $transaction->offer->update(['status' => 'completed']);
                    }

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
                        'reason_category' => 'Revision Declined by Helper',
                        'description' => $data['dispute_reason'],
                        'status' => 'pending',
                    ]);

                    // Store dispute evidence images
                    foreach ($uploadedImages as $imageFile) {
                        $path = $imageFile->store('transactions/disputes', 'public');
                        $uploadedPaths[] = $path;

                        $report->images()->create([
                            'url' => $path,
                            'file_name' => $imageFile->getClientOriginalName(),
                            'file_type' => $imageFile->getClientMimeType(),
                            'type' => 'dispute_evidence',
                        ]);
                    }

                    return $this->successPayload($report, 'Revision declined. Transaction moved to disputed status for admin mediation.');
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
    public function requestRefund(string $transactionId, string $requesterId, array $data, array $uploadedImages = []): array
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($transactionId, $requesterId, $data, $uploadedImages, &$uploadedPaths) {
                $transaction = Transaction::whereKey($transactionId)->lockForUpdate()->first();

                if (!$transaction) {
                    throw ValidationException::withMessages([
                        'transaction' => ['Transaction not found.']
                    ]);
                }

                if ($transaction->requester_id !== $requesterId) {
                    throw ValidationException::withMessages([
                        'requester' => ['Only the requester can file a refund for this transaction.']
                    ]);
                }

                // Block refund if work is still in progress or under revision
                $blockedStatuses = ['on_progress', 'revision', 'pending_revision'];
                if (in_array($transaction->status, $blockedStatuses)) {
                    throw ValidationException::withMessages([
                        'status' => ['Refund cannot be requested while the transaction is still in progress or under revision. Current status: ' . $transaction->status]
                    ]);
                }

                $escrow = $transaction->escrow;
                if (!$escrow || $escrow->status !== 'held') {
                    throw ValidationException::withMessages([
                        'escrow' => ['Refund can only be requested while funds are still held in escrow. Current escrow status: ' . ($escrow ? $escrow->status : 'none')]
                    ]);
                }

                // Check for existing pending/processing refund
                $hasActiveRefund = Refund::where('transaction_id', $transaction->id)
                    ->whereIn('status', ['pending', 'processing'])
                    ->exists();

                if ($hasActiveRefund) {
                    throw ValidationException::withMessages([
                        'refund' => ['An active refund request for this transaction already exists and is being processed.']
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

                // Store uploaded proof images
                foreach ($uploadedImages as $imageFile) {
                    $path = $imageFile->store('transactions/refunds', 'public');
                    $uploadedPaths[] = $path;

                    $transaction->images()->create([
                        'url' => $path,
                        'file_name' => $imageFile->getClientOriginalName(),
                        'file_type' => $imageFile->getClientMimeType(),
                        'type' => 'refund',
                    ]);
                }

                // Update transaction status
                $transaction->update([
                    'status' => 'pending_refund',
                ]);

                if ($transaction->helper) {
                    try {
                        $projectTitle = $transaction->offer->post->title ?? 'Project';

                        app(NotificationService::class)->sendToUser(
                            $transaction->helper,
                            'Refund Request Submitted',
                            'A refund request has been filed for "' . $projectTitle . '". The request is currently being processed.',
                            [
                                'transaction_id' => (string) $transaction->id,
                                'offer_id'       => (string) $transaction->offer_id,
                                'screen'         => 'offer_list',
                            ],
                            'refund_submitted'
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to send refund submitted notification: ' . $e->getMessage());
                    }
                }

                return $this->successPayload($refund, 'Refund request submitted successfully. Awaiting helper approval.');
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
     * Respond to a refund request by the helper (Approve or Reject).
     */
    public function respondToRefund(string $refundId, string $helperId, array $data): array
    {
        try {
            return DB::transaction(function () use ($refundId, $helperId, $data) {
                $refund = Refund::whereKey($refundId)->lockForUpdate()->first();

                if (!$refund) {
                    throw ValidationException::withMessages([
                        'refund' => ['Refund request data not found.']
                    ]);
                }

                if ($refund->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' => ['This refund request has already been processed. Current status: ' . $refund->status]
                    ]);
                }

                $transaction = $refund->transaction;
                if (!$transaction || $transaction->helper_id !== $helperId) {
                    throw ValidationException::withMessages([
                        'helper' => ['Only the assigned helper on this transaction can respond to the refund request.']
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

                    if ($transaction->offer) {
                        $transaction->offer->update(['status' => 'completed']);
                    }

                    return $this->successPayload($refund->load('transaction'), 'Refund request approved. Funds will be returned to the requester.');
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

                    if ($transaction->offer) {
                        $transaction->offer->update(['status' => 'completed']);
                    }

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
                        'reason_category' => 'Refund Declined by Helper',
                        'description' => $data['dispute_reason'] ?? 'Helper declined the refund request from requester.',
                        'status' => 'pending',
                    ]);

                    return $this->successPayload($report, 'Refund declined. Transaction moved to disputed status for admin mediation.');
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
                'reviews' => function ($q) use ($userId) {
                    $q->where('reviewed_id', $userId)
                      ->with(['reviewer.photoProfile', 'reviewed.photoProfile', 'images']);
                }
            ])
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('helper_id', $userId);
            })
            ->whereHas('reviews', function ($q) use ($userId) {
                $q->where('reviewed_id', $userId);
            })
            ->latest()
            ->get();

            return $this->successPayload($transactions, 'Reviewed transaction history retrieved successfully.');
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
                        'transaction' => ['Transaction not found.']
                    ]);
                }

                // Check transaction status (must be completed to be reviewed)
                if ($transaction->status !== 'completed') {
                    throw ValidationException::withMessages([
                        'status' => ['A review can only be submitted when the transaction is "completed". Current status: ' . $transaction->status]
                    ]);
                }

                // Check if user is requester or helper
                $isRequester = $transaction->requester_id === $reviewerId;
                $isHelper = $transaction->helper_id === $reviewerId;

                if (!$isRequester && !$isHelper) {
                    throw ValidationException::withMessages([
                        'user' => ['Only the requester or helper involved in this transaction can submit a review.']
                    ]);
                }

                // Check if user has already reviewed this transaction
                $alreadyReviewed = Review::where('transaction_id', $transactionId)
                    ->where('reviewer_id', $reviewerId)
                    ->exists();

                if ($alreadyReviewed) {
                    throw ValidationException::withMessages([
                        'review' => ['You have already submitted a review for this transaction.']
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

                if ($review->reviewed) {
                    try {
                        $projectTitle = $transaction->offer->post->title ?? 'Project';

                        app(NotificationService::class)->sendToUser(
                            $review->reviewed,
                            'You Received a New Review!',
                            'Someone has just rated their experience working with you on "' . $projectTitle . '". View your feedback now.',
                            [
                                'transaction_id' => (string) $transaction->id,
                                'review_id'      => (string) $review->id,
                                'screen'         => 'offer_list',
                            ],
                            'new_review'
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to send new review notification: ' . $e->getMessage());
                    }
                }

                return $this->successPayload($review, 'Review submitted successfully.', 201);
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
                throw new Exception('Transaction not found.');
            }

            $helper = $transaction->helper;
            if (!$helper) {
                throw new Exception('Helper not found for this transaction.');
            }

            // Get helper's primary bank account
            $bankAccount = $helper->primaryBankAccount;
            if (!$bankAccount) {
                throw ValidationException::withMessages([
                    'bank_account' => ['Helper has not registered a primary bank account for automatic disbursement.']
                ]);
            }

            $escrow = $transaction->escrow;
            if (!$escrow) {
                throw new Exception('Escrow data not found for this transaction.');
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
                throw new Exception('Failed to transfer to helper bank via Midtrans: ' . $errorMessage);
            }

            $resJson = $response->json();
            $payoutInfo = $resJson['payouts'][0] ?? [];
            $referenceNo = $payoutInfo['reference_no'] ?? 'N/A';
            $payoutStatus = $payoutInfo['status'] ?? 'pending';

            // Update escrow release notes with disbursement details
            $escrow->update([
                'release_notes' => trim($escrow->release_notes . "\nMidtrans Iris automatic transfer successful. Status: " . $payoutStatus . ", Ref: " . $referenceNo),
            ]);

            return $this->successPayload([
                'transaction_id' => $transaction->id,
                'reference_no' => $referenceNo,
                'status' => $payoutStatus,
                'amount' => $netAmount,
                'bank' => $bankAccount->bank_name,
                'account_number' => $bankAccount->account_number,
            ], 'Funds successfully transferred to helper\'s bank account.');

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
                        'transaction' => ['Transaction not found.']
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

                // If status was updated to completed, update the offer status too
                if (isset($updateData['status']) && $updateData['status'] === 'completed') {
                    $offer = $transaction->offer;
                    if ($offer) {
                        $offer->update([
                            'status' => OfferingStatusEnum::COMPLETED->value,
                        ]);

                        $post = $offer->post;
                        if ($post && $post->type === 'offer') {
                            if ($post->offerDetail) {
                                $post->offerDetail->update([
                                    'status' => ActiveOffEnum::ACTIVE->value,
                                ]);
                            }
                        }
                    }
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

                return $this->successPayload($transaction, 'Transaction updated successfully.');
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
                        'transaction' => ['Transaction not found.']
                    ]);
                }

                // Check authorization (only requester or helper can cancel)
                if ($transaction->requester_id !== $userId && $transaction->helper_id !== $userId) {
                    throw ValidationException::withMessages([
                        'authorization' => ['You are not authorized to cancel this transaction.']
                    ]);
                }

                // Only allow cancellation if status is pending
                if ($transaction->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' => ['Transaction can only be cancelled when status is "pending". Current status: ' . $transaction->status]
                    ]);
                }

                // Update transaction status to cancelled
                $transaction->update([
                    'status' => 'cancelled',
                ]);

                if ($transaction->offer) {
                    $transaction->offer->update(['status' => 'completed']);
                }

                // Update payment status if exists
                if ($transaction->payment) {
                    $transaction->payment->update([
                        'status' => 'failed',
                    ]);
                }

                return $this->successPayload($transaction, 'Transaction cancelled successfully.');
            });
        } catch (ValidationException $e) {
            return $this->errorPayload($e->getMessage(), $e->errors(), 422);
        } catch (Exception $e) {
            return $this->errorPayload($e->getMessage(), [$e->getFile() . ':' . $e->getLine()], 500);
        }
    }

    /**
     * Automatically handle all overdue transactions (helper melewati deadline).
     * Sets status to pending_refund and creates a Refund record.
     * Can be escalated to disputed if helper declines via respondToRefund().
     *
     * @return array Summary of processed transactions.
     */
    public function handleOverdueTransactions(): array
    {
        $overdueTransactions = Transaction::with(['escrow', 'payment', 'helper', 'requester', 'offer.post'])
            ->where('status', 'on_progress')
            ->where('deadline', '<', now())
            ->get();

        if ($overdueTransactions->isEmpty()) {
            return $this->successPayload([], 'No overdue transactions found.');
        }

        $processed = [];
        $failed    = [];

        foreach ($overdueTransactions as $transaction) {
            try {
                DB::transaction(function () use ($transaction) {
                    $escrow = $transaction->escrow;

                    // Guard: escrow must be held for refund to proceed
                    if (! $escrow || $escrow->status !== 'held') {
                        throw new Exception("Escrow tidak valid untuk transaksi {$transaction->id}.");
                    }

                    // Guard: avoid duplicate pending refund
                    $hasActiveRefund = Refund::where('transaction_id', $transaction->id)
                        ->whereIn('status', ['pending', 'processing'])
                        ->exists();

                    if ($hasActiveRefund) {
                        throw new Exception("Refund aktif sudah ada untuk transaksi {$transaction->id}.");
                    }

                    // Create automatic refund record
                    Refund::create([
                        'transaction_id'   => $transaction->id,
                        'payment_id'       => $escrow->payment_id ?? $transaction->payment?->id,
                        'user_id'          => $transaction->requester_id,
                        'amount'           => $escrow->held_amount,
                        'reason'           => 'Overdue: helper melewati deadline tanpa menyelesaikan pekerjaan.',
                        'status'           => 'pending',
                        'gateway_refund_id' => 'REF-OVERDUE-' . strtoupper(uniqid()),
                    ]);

                    // Move transaction to pending_refund
                    $transaction->update(['status' => 'pending_refund']);
                });

                $processed[] = $transaction->id;

                // Notify helper
                if ($transaction->helper) {
                    try {
                        $projectTitle = $transaction->offer?->post?->title ?? 'Project';
                        app(NotificationService::class)->sendToUser(
                            $transaction->helper,
                            'Transaction Overdue – Automatic Refund',
                            'You have passed the deadline for "' . $projectTitle . '". An automatic refund request has been created. Please respond in the app.',
                            [
                                'transaction_id' => (string) $transaction->id,
                                'screen'         => 'transaction_detail',
                            ],
                            'transaction_overdue'
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to send overdue notification to helper: ' . $e->getMessage());
                    }
                }

                // Notify requester
                if ($transaction->requester) {
                    try {
                        $projectTitle = $transaction->offer?->post?->title ?? 'Project';
                        app(NotificationService::class)->sendToUser(
                            $transaction->requester,
                            'Helper Missed the Deadline',
                            'The helper on "' . $projectTitle . '" has missed the deadline. An automatic refund is being processed.',
                            [
                                'transaction_id' => (string) $transaction->id,
                                'screen'         => 'transaction_detail',
                            ],
                            'transaction_overdue'
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to send overdue notification to requester: ' . $e->getMessage());
                    }
                }

            } catch (Exception $e) {
                Log::error("handleOverdueTransactions: Gagal memproses transaksi {$transaction->id}: " . $e->getMessage());
                $failed[] = ['id' => $transaction->id, 'reason' => $e->getMessage()];
            }
        }

        return $this->successPayload([
            'processed_count' => count($processed),
            'failed_count'    => count($failed),
            'processed_ids'   => $processed,
            'failed'          => $failed,
        ], 'Overdue processing complete.');
    }

    /**
     * Get a transaction by its ID with all its relations.
     *
     * @param string $id
     * @return array
     */
    public function getTransactionById(string $id): array
    {
        $transaction = Transaction::with([
            'completionImages',
            'helper',
            'helper.photoProfile',
            'requester',
            'requester.photoProfile',
            'offer.post.images',
            'offer.post.category',
            'payment',
            'escrow',
            'revisions',
            'revisions.images',
            'reviews',
            'refunds',
        ])->find($id);

        if (!$transaction) {
            return $this->errorPayload('Transaction not found.', [], 404);
        }

        return $this->successPayload($transaction, 'Transaction retrieved successfully.');
    }
}

