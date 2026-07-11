<?php

namespace App\Service\Offer;

use App\Models\BankAccount;
use App\Models\Post;
use App\Models\User;
use App\Service\Notification\NotificationService;
use App\Traits\ServiceResponse;
use Illuminate\Validation\ValidationException;
use Log;

class HireHelperService
{
    use ServiceResponse;

    public function bookHelperService(Post $post, array $data, string $requesterId)
    {
        if ($post->type !== 'offer') {
            throw ValidationException::withMessages([
                'post_id' => ['You can only book a helper service on an offer post.'],
            ]);
        }

        if ($post->user_id === $requesterId) {
            throw ValidationException::withMessages([
                'post_id' => ['You cannot book your own service.'],
            ]);
        }

        $helperHasBank = BankAccount::where('user_id', $post->user_id)->exists();
        if (! $helperHasBank) {
            throw ValidationException::withMessages([
                'post_id' => ['The helper must register a bank account before their service can be booked.'],
            ]);
        }

        $hasBooked = $post->offers()
            ->where('requester_id', $requesterId)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'rejected')
            ->exists();

        if ($hasBooked) {
            throw ValidationException::withMessages([
                'post_id' => ['You have already booked this helper service.'],
            ]);
        }

        // For is_multiple posts, we allow multiple bookings and concurrent transactions.
        // Therefore, we do not block new bookings if there's an active transaction with a different requester.

        $basePrice = $post->offerDetail->base_price;

        if ($data['offered_price'] < $basePrice) {
            throw ValidationException::withMessages([
                'offered_price' => ['The offered price must be at least '.$basePrice.'.'],
            ]);
        }

        $offer = $post->offers()->create([
            'helper_id' => $post->user_id,
            'requester_id' => $requesterId,
            'initiated_by' => $requesterId,
            'offered_price' => $data['offered_price'],
        ]);

        $postOwner = User::find($post->user_id);
        if ($postOwner) {
            try {
                app(NotificationService::class)->sendToUser(
                    $postOwner,
                    'New Job Request Received!',
                    $offer->requester->first_name.' has sent you a direct request for "'.$post->title.'". Review the details and send your offer now!',
                    [
                        'post_id' => (string) $post->id,
                        'offer_id' => (string) $offer->id,
                        'screen' => 'offer_list',
                    ],
                    'new_offer'
                );
            } catch (\Exception $e) {
                Log::error('Failed to send booking notification: '.$e->getMessage());
            }
        }

        return $this->successPayload($offer, 'Helper service booked successfully.');
    }

    public function getOffersForPost(Post $post)
    {
        return $post->offers()->with(['helper', 'requester'])->get();
    }
}
