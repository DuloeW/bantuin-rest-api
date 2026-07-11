<?php

namespace App\Service\Offer;

use App\Enum\ActiveOffEnum;
use App\Enum\OfferingStatusEnum;
use App\Enum\OpenCloseEnum;
use App\Models\BankAccount;
use App\Models\Offer;
use App\Models\Post;
use App\Models\User;
use App\Service\Notification\NotificationService;
use App\Traits\ServiceResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Log;

class OfferHelpService
{
    use ServiceResponse;

    public function applyForJob(Post $post, array $data, string $helperId)
    {
        $user = User::find($helperId);

        if (!$user->email_verified_at) {
            return $this->errorPayload('Email must be verified to apply for this job.', null, 403);
        }

        if ($post->type !== 'request') {
            throw ValidationException::withMessages([
                'post_id' => ['You can only apply for a job on a request post.'],
            ]);
        }

        if ($post->user_id === $helperId) {
            throw ValidationException::withMessages([
                'post_id' => ['You cannot apply for a job on your own post.'],
            ]);
        }

        $hasBankAccount = BankAccount::where('user_id', $helperId)->exists();
        if (! $hasBankAccount) {
            throw ValidationException::withMessages([
                'bank_account' => ['You must register a bank account before applying for a job.'],
            ]);
        }

        $hasApplied = $post->offers()
            ->where('helper_id', $helperId)
            ->exists();

        if ($hasApplied) {
            throw ValidationException::withMessages([
                'post_id' => ['You have already applied for this job.'],
            ]);
        }

        $minPrice = $post->requestDetail->min_price;

        if ($minPrice !== null && $data['offered_price'] < $minPrice) {
            throw ValidationException::withMessages([
                'offered_price' => ['The offered price must be at least '.$minPrice.'.'],
            ]);
        }

        $offer = $post->offers()->create([
            'helper_id' => $helperId,
            'requester_id' => $post->user_id,
            'initiated_by' => $helperId,
            'offered_price' => $data['offered_price'],
        ]);

        $postOwner = User::find($post->user_id);
        if ($postOwner) {
            try {
                app(NotificationService::class)->sendToUser(
                    $postOwner,
                    'New Offer Received!',
                    $offer->helper->first_name.' has sent an offer for your request "'.$post->title.'". Check it out now!',
                    [
                        'post_id' => (string) $post->id,
                        'offer_id' => (string) $offer->id,
                        'screen' => 'offer_list',
                    ],
                    'new_offer'
                );
            } catch (\Exception $e) {
                Log::error('Failed to send new offer notification: '.$e->getMessage());
            }
        }

        return $this->successPayload($offer, 'Offer created successfully.');
    }

    public function getOffersForPost(Post $post)
    {
        return $post->offers()->with(['helper', 'requester'])->get();
    }

    public function acceptHelper(Offer $offer, string $helperId)
    {
        // Ensure the requester (actor) owns the post
        $post = $offer->post;

        if (! $post) {
            throw ValidationException::withMessages([
                'offer' => ['Related post not found.'],
            ]);
        }

        if ($post->user_id !== $helperId) {
            throw ValidationException::withMessages([
                'offer' => ['Only the post owner can accept an offer.'],
            ]);
        }

        if (! in_array($post->type, ['request', 'offer'])) {
            throw ValidationException::withMessages([
                'post_id' => ['Invalid post type.'],
            ]);
        }

        if ($offer->post_id !== $post->id) {
            throw ValidationException::withMessages([
                'offer' => ['Offer does not belong to the given post.'],
            ]);
        }

        if ($offer->status !== OfferingStatusEnum::PENDING->value) {
            if ($offer->status === OfferingStatusEnum::ACCEPTED->value) {
                return $this->successPayload($offer, 'Offer already accepted.');
            }

            throw ValidationException::withMessages([
                'offer' => ['Only pending offers can be accepted.'],
            ]);
        }

        // Additional business checks for request posts
        if ($post->type === 'request') {
            $requestDetail = $post->requestDetail;
            if (! $requestDetail) {
                throw ValidationException::withMessages([
                    'post_id' => ['Request details not found for this post.'],
                ]);
            }

            if (isset($requestDetail->deadline) && Carbon::now()->greaterThan($requestDetail->deadline)) {
                throw ValidationException::withMessages([
                    'post_id' => ['The request deadline has passed.'],
                ]);
            }

            $minPrice = $requestDetail->min_price;
            if ($minPrice !== null && $offer->offered_price < $minPrice) {
                throw ValidationException::withMessages([
                    'offered_price' => ['The offered price is lower than the minimum allowed.'],
                ]);
            }
        }

        // Perform acceptance atomically to avoid race conditions
        DB::transaction(function () use ($post, $offer) {
            $lockedPost = Post::whereKey($post->id)->lockForUpdate()->first();

            if (! $lockedPost) {
                throw ValidationException::withMessages([
                    'post_id' => ['Post not found.'],
                ]);
            }

            $lockedOffer = Offer::whereKey($offer->id)->lockForUpdate()->first();

            if (! $lockedOffer) {
                throw ValidationException::withMessages([
                    'offer' => ['Offer not found.'],
                ]);
            }

            if (! $lockedPost->is_multiple) {
                // For single-use posts: ensure no other offer is already accepted
                $alreadyAccepted = $lockedPost->offers()
                    ->where('status', OfferingStatusEnum::ACCEPTED->value)
                    ->exists();
                if ($alreadyAccepted) {
                    throw ValidationException::withMessages([
                        'offer' => ['Another offer has already been accepted for this post.'],
                    ]);
                }

                // Reject all other offers
                $lockedPost->offers()
                    ->where('id', '!=', $lockedOffer->id)
                    ->update(['status' => OfferingStatusEnum::REJECTED->value]);

                // Close the post
                if ($lockedPost->requestDetail) {
                    $lockedPost->requestDetail()->update(['status' => OpenCloseEnum::CLOSED->value]);
                }
                if ($lockedPost->offerDetail) {
                    $lockedPost->offerDetail()->update(['status' => ActiveOffEnum::OFF->value]);
                }
            }

            // Accept selected offer
            $lockedOffer->update(['status' => OfferingStatusEnum::ACCEPTED->value]);
        });

        return $this->successPayload($offer->fresh(), 'Offer accepted successfully.');
    }
}
