<?php

namespace App\Service\Transaction;

use App\Models\Transaction;
use App\Traits\ServiceResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
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
        $query = Transaction::with(['completionImages', 'helper', 'requester', 'offer.post', 'payment', 'escrow'])
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
}
