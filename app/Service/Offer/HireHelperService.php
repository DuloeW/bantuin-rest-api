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
        if($post->type  !== 'service') {
            throw ValidationException::withMessages([
                'post_id' => ['You can only book a helper service on a service post.']
            ]);
        }

        if($post->user_id === $requesterId) {
            throw ValidationException::withMessages([
                'post_id' => ['You cannot book a helper service on your own post.']
            ]);
        }

        $hasApplied = $post->offers()->where('helper_id', $requesterId)->exists();

        if($hasApplied) {
            throw ValidationException::withMessages([
                'post_id' => ['You have already booked this helper service.']
            ]);
        }

        $basePrice = $post->serviceDetail->base_price;
        if($basePrice > $data['offered_price']) {
            throw ValidationException::withMessages([
                'offered_price' => ['The offered price must be at least ' . $basePrice . '.']
            ]);
        }

        $post = $post->offers()->create([
            'helper_id' => $requesterId,
            'requester_id' => $post->user_id,
            'initiated_by' => $requesterId,
            'offered_price' => $data['offered_price'],
        ]);

        return $this->successPayload($post, 'Helper service booked successfully.');
    }
}