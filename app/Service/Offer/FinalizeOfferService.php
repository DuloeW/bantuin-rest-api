<?php

namespace App\Service\Offer;

use App\Enum\ActiveOffEnum;
use App\Enum\OfferingStatusEnum;
use App\Enum\OpenCloseEnum;
use App\Models\Offer;
use App\Models\Post;
use App\Models\Transaction;
use App\Traits\ServiceResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeOfferService
{
    use ServiceResponse;

    /**
     * Admin fee percentage (2%)
     */
    const ADMIN_FEE_PERCENT = 0.02;

    /**
     * Finalize an offer: accept it, create a transaction, and close the post.
     *
     * Yang bisa finalize adalah REQUESTER (offer->requester_id), berlaku untuk kedua tipe post:
     * - Post 'request' : requester = pemilik post (yang pasang lowongan)
     * - Post 'offer'   : requester = yang booking service helper (bukan pemilik post)
     *
     * @param  Offer  $offer
     * @param  string $actorId  The authenticated user's ID (must be the requester of the offer)
     * @param  array  $data     Validated input: deadline, work_notes (optional), agreed_price (optional)
     * @return array
     */
    public function finalize(Offer $offer, string $actorId, array $data): array
    {
        \Illuminate\Support\Facades\Log::info('FINALIZE OFFER SERVICE CALLED:', [
            'data' => $data,
            'offer_id' => $offer->id,
            'actor_id' => $actorId,
        ]);

        $post = $offer->post;

        // 1. Pastikan post ada
        if (!$post) {
            throw ValidationException::withMessages([
                'offer' => ['Related post not found.'],
            ]);
        }

        // 2. Hanya requester offer yang bisa finalize
        //    - Post 'request' : offer->requester_id = post->user_id (requester pasang lowongan)
        //    - Post 'offer'   : offer->requester_id = user yang booking (bukan pemilik post)
        if ($offer->requester_id !== $actorId) {
            throw ValidationException::withMessages([
                'offer' => ['Only the requester of this offer can finalize it.'],
            ]);
        }

        // 3. Hanya offer yang statusnya pending yang bisa di-finalize
        if ($offer->status !== OfferingStatusEnum::PENDING->value) {
            if ($offer->status === OfferingStatusEnum::ACCEPTED->value) {
                // Cek apakah sudah ada transaksi untuk offer ini
                $hasTransaction = Transaction::where('offer_id', $offer->id)->exists();
                if ($hasTransaction) {
                    throw ValidationException::withMessages([
                        'offer' => ['This offer has already been finalized.'],
                    ]);
                }
                // Jika belum ada transaksi, ijinkan untuk diproses (lanjut ke pembuatan transaksi)
            } else {
                throw ValidationException::withMessages([
                    'offer' => ['Only pending or accepted offers without transactions can be finalized.'],
                ]);
            }
        }

        // 4. Pastikan belum ada offer lain yang diterima
        $alreadyAccepted = $post->offers()
            ->where('status', OfferingStatusEnum::ACCEPTED->value)
            ->exists();

        if ($alreadyAccepted) {
            throw ValidationException::withMessages([
                'offer' => ['Another offer has already been accepted for this post.'],
            ]);
        }

        // 5. Tentukan harga kesepakatan
        $agreedPrice = isset($data['agreed_price'])
            ? (float) $data['agreed_price']
            : (float) $offer->offered_price;

        $adminFee   = round($agreedPrice * self::ADMIN_FEE_PERCENT, 2);
        $totalPrice = $agreedPrice + $adminFee;

        $transaction = DB::transaction(function () use ($post, $offer, $actorId, $data, $agreedPrice, $adminFee, $totalPrice) {
            // Lock post & offer untuk menghindari race condition
            $lockedPost  = Post::whereKey($post->id)->lockForUpdate()->first();
            $lockedOffer = Offer::whereKey($offer->id)->lockForUpdate()->first();

            // Reject semua offer lain pada post yang sama
            $lockedPost->offers()
                ->where('id', '!=', $lockedOffer->id)
                ->update(['status' => OfferingStatusEnum::REJECTED->value]);

            // Accept offer yang dipilih
            $lockedOffer->update(['status' => OfferingStatusEnum::ACCEPTED->value]);

            // Tutup detail post berdasarkan tipe post (karena kolom status di tabel posts sudah dihapus)
            if ($lockedPost->requestDetail) {
                $lockedPost->requestDetail()->update(['status' => OpenCloseEnum::CLOSED->value]);
            }
            if ($lockedPost->offerDetail) {
                $lockedPost->offerDetail()->update(['status' => ActiveOffEnum::OFF->value]);
            }

            // Buat transaksi baru
            $transaction = Transaction::create([
                'offer_id'      => $lockedOffer->id,
                'requester_id'  => $lockedOffer->requester_id,
                'helper_id'     => $lockedOffer->helper_id,
                'final_price'   => $agreedPrice,
                'admin_fee'     => $adminFee,
                'total_price'   => $totalPrice,
                'deadline'      => $data['deadline'],
                'work_notes'    => $data['work_notes'] ?? null,
                'status'        => 'pending',
            ]);

            return $transaction;
        });

        return $this->successPayload([
            'transaction' => $transaction->fresh(),
            'offer'       => $offer->fresh(),
        ], 'Offer finalized and transaction created successfully.');
    }
}
