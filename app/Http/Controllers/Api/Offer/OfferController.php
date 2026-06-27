<?php

namespace App\Http\Controllers\Api\Offer;

use App\Http\Controllers\Controller;
use App\Models\Post;
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

    public function __construct(OfferHelpService $offerHelpService, HireHelperService $hireHelperService)
    {
        $this->offerHelpService = $offerHelpService;
        $this->hireHelperService = $hireHelperService;
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
}
