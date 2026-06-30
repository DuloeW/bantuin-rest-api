<?php

namespace App\Http\Controllers\Api\Offer;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Post;
use App\Service\Offer\FinalizeOfferService;
use App\Service\Offer\HireHelperService;
use App\Service\Offer\OfferHelpService;
use App\Traits\ServiceResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    use ServiceResponse;
    protected OfferHelpService $offerHelpService;
    protected HireHelperService $hireHelperService;
    protected FinalizeOfferService $finalizeOfferService;

    public function __construct(
        OfferHelpService $offerHelpService,
        HireHelperService $hireHelperService,
        FinalizeOfferService $finalizeOfferService
    ) {
        $this->offerHelpService      = $offerHelpService;
        $this->hireHelperService     = $hireHelperService;
        $this->finalizeOfferService  = $finalizeOfferService;
    }

    public function applyForJob(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_id' => 'required|exists:posts,id',
            'offered_price' => 'required|numeric|min:0',
        ]);

        $post = Post::findOrFail($data['post_id']);


        $offer = $this->offerHelpService->applyForJob($post, $data, auth('sanctum')->id());

        return response()->json($offer, $offer['code']);
    }

    public function bookHelperService(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_id' => 'required|exists:posts,id',
            'offered_price' => 'required|numeric|min:0',
        ]);

        $post = Post::findOrFail($data['post_id']);

        $offer = $this->hireHelperService->bookHelperService($post, $data, auth('sanctum')->id());

        return response()->json($offer, $offer['code']);
    }

    public function acceptHelper(Request $request): JsonResponse
    {
        $data = $request->validate([
            'offer_id' => 'required|exists:offers,id',
        ]);

        $offer = $this->offerHelpService->acceptHelper($data['offer_id'], auth('sanctum')->id());

        return response()->json($offer, $offer['code']);
    }

    public function getOffersForPost(string $postId): JsonResponse
    {
        $post = Post::findOrFail($postId);

        $offers = $this->offerHelpService->getOffersForPost($post);

        return response()->json($this->successPayload($offers), 200);
    }

    /**
     * Finalize an offer: accept it, create a transaction, and close the post.
     * Called from the "Final Service Agreement" form in the mobile app.
     */
    public function finalizeOffer(Request $request, string $offerId): JsonResponse
    {
        $data = $request->validate([
            'deadline'      => 'required|date|after:now',
            'work_notes'    => 'nullable|string|max:2000',
            'agreed_price'  => 'nullable|numeric|min:0',
        ]);

        $offer  = Offer::findOrFail($offerId);
        $result = $this->finalizeOfferService->finalize($offer, auth('sanctum')->id(), $data);

        return response()->json($result, $result['code']);
    }
}
