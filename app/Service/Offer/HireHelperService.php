<?php

namespace App\Service\Offer;

use App\Models\Post;
use App\Traits\ServiceResponse;
use Illuminate\Validation\ValidationException;

class HireHelperService
{
    use ServiceResponse;

    public function bookHelperService(Post $post, array $data, string $requesterId)
    {
        if ($post->type !== 'offer') {
            throw ValidationException::withMessages([
                'post_id' => ['You can only book a helper service on an offer post.']
            ]);
        }

        if ($post->user_id === $requesterId) {
            throw ValidationException::withMessages([
                'post_id' => ['You cannot book your own service.']
            ]);
        }

        $hasBooked = $post->offers()
            ->where('requester_id', $requesterId)
            ->exists();

        if ($hasBooked) {
            throw ValidationException::withMessages([
                'post_id' => ['You have already booked this helper service.']
            ]);
        }

        $basePrice = $post->offerDetail->base_price;

        if ($data['offered_price'] < $basePrice) {
            throw ValidationException::withMessages([
                'offered_price' => ['The offered price must be at least ' . $basePrice . '.']
            ]);
        }

        $offer = $post->offers()->create([
            'helper_id' => $post->user_id,
            'requester_id' => $requesterId,
            'initiated_by' => $requesterId,
            'offered_price' => $data['offered_price'],
        ]);

        return $this->successPayload($offer, 'Helper service booked successfully.');
    }

    public function getOffersForPost(Post $post)
    {
        return $post->offers()->with('requester')->get();
    }
}
