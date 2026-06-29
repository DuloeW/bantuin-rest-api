<?php

namespace App\Http\Controllers\Api\Post;

use App\Http\Controllers\Controller;
use App\Service\Post\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    protected PostService $postService;
    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }


    public function getAll()
    {
        $result = $this->postService->getAllPosts();

        return response()->json($result, $result['code']);
    }

    public function getTotalUserPosts()
    {
        $result = $this->postService->getTotalUserPosts();

        return response()->json($result, $result['code']);
    }

    public function getAllWithRequestDetails()
    {
        $result = $this->postService->getAllPostsWithRequestDetails();

        return response()->json($result, $result['code']);
    }

    public function getAllWithOfferDetails()
    {
        $result = $this->postService->getAllWithOfferDetails();

        return response()->json($result, $result['code']);
    }

    public function createRequest(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:offer,request',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'min_price' => 'required_if:type,request|numeric',
            'max_price' => 'required_if:type,request|numeric|gte:min_price',
            'deadline' => 'required_if:type,request|date',
            'method_service' => 'required_if:type,request|string',
            'province_id' => 'required_if:type,request|integer|exists:indonesia_provinces,id',
            'city_id' => 'required_if:type,request|integer|exists:indonesia_cities,id',
            'district_id' => 'required_if:type,request|integer|exists:indonesia_districts,id',    
            'village_id' => 'required_if:type,request|integer|exists:indonesia_villages,id',
            'address_details' => 'required_if:type,request|string',
            'images' => 'required|array|max:5',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'location' => 'required_if:type,request|array',
            'location.latitude' => 'required_if:type,request|numeric|between:-90,90',
            'location.longitude' => 'required_if:type,request|numeric|between:-180,180',
            'published_until' => 'required_if:type,request|date|after:today',
        ]);

        $data = $request->all();

        $uploadedImages = $request->file('images');

        $result = $this->postService->createRequestPost($data, $uploadedImages);

        return response()->json($result, $result['code']);
    }

    public function createOffer(Request $request)
    {
       $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:offer,request',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required_if:type,offer|numeric',
            'time_start' => 'required_if:type,offer|date_format:H:i',
            'time_end' => 'required_if:type,offer|date_format:H:i',
            'portfolio_url' => 'sometimes|nullable|string|max:500',
            'experience_years' => 'required_if:type,offer|integer|min:0',
            'province_id' => 'required_if:type,offer|integer|exists:indonesia_provinces,id',
            'city_id' => 'required_if:type,offer|integer|exists:indonesia_cities,id',
            'district_id' => 'required_if:type,offer|integer|exists:indonesia_districts,id',    
            'village_id' => 'required_if:type,offer|integer|exists:indonesia_villages,id',
            'address_details' => 'required_if:type,offer|string',
            'status' => 'required_if:type,offer|in:active,off',
            'images' => 'required|array|max:5',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'location' => 'required_if:type,offer|array',
            'location.latitude' => 'required_if:type,offer|numeric|between:-90,90',
            'location.longitude' => 'required_if:type,offer|numeric|between:-180,180',
        ]);

        $data = $request->all();

        $uploadedImages = $request->file('images');

        $result = $this->postService->createOfferPost($data, $uploadedImages);

        return response()->json($result, $result['code']);
    }


    public function delete(string $id)
    {   
        $result = $this->postService->deletePost($id);

        return response()->json($result, $result['code']);
    }

}
