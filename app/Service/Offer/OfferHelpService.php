<?php

namespace App\Service\Offer;

use App\Models\Post;
use App\Traits\ServiceResponse;
use Illuminate\Validation\ValidationException;

class OfferHelpService
{
    use ServiceResponse;
    public function applyForJob(Post $post, array $data, string $helperId)
    {   
        if($post->type !== 'request') {
            throw ValidationException::withMessages([
                'post_id' => ['You can only apply for a job on a request post.']
            ]);
        }

        $hasApplied = $post->offers()->where('helper_id', $helperId)->exists();

        if($hasApplied) {
            throw ValidationException::withMessages([
                'post_id' => ['You have already applied for this job.']
            ]);
        }

        if($post->user_id === $helperId) {
            throw ValidationException::withMessages([
                'post_id' => ['You cannot apply for a job on your own post.']
            ]);
        }

        $minPrice = $post->requestDetail->min_price;
        if($minPrice > $data['offered_price']) {
            throw ValidationException::withMessages([
                'offered_price' => ['The offered price must be at least ' . $minPrice . '.']
            ]);
        }

        $post = $post->offers()->create([
            'helper_id' => $helperId,
            'requester_id' => $post->user_id,
            'initiated_by' => $helperId,
            'offered_price' => $data['offered_price'],
        ]);

        return $this->successPayload($post, 'Offer created successfully.');
    }
}