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

    public function getAllWithServiceDetails()
    {
        $result = $this->postService->getAllPostsWithServiceDetails();

        return response()->json($result, $result['code']);
    }

    public function createRequest(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:service,request',
            'description' => 'required|string',
            'category_id' => 'required|uuid',
            'min_price' => 'required_if:type,request|numeric',
            'max_price' => 'required_if:type,request|numeric|gte:min_price',
            'deadline' => 'required_if:type,request|date',
            'method_service' => 'required_if:type,request|string',
            'province' => 'required_if:type,request|string',
            'regency' => 'required_if:type,request|string',
            'district' => 'required_if:type,request|string',    
            'village' => 'required_if:type,request|string',
            'address_details' => 'required_if:type,request|string',
            'status' => 'required_if:type,request|in:open,closed',
            'images' => 'required|array|max:5',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'location' => 'required_if:type,request|array',
            'location.latitude' => 'required_if:type,request|numeric|between:-90,90',
            'location.longitude' => 'required_if:type,request|numeric|between:-180,180',
        ]);

        $data = $request->all();

        $uploadedImages = $request->file('images');

        $result = $this->postService->createRequestPost($data, $uploadedImages);

        return response()->json($result, $result['code']);
    }

    public function createService(Request $request)
    {
       $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:service,request',
            'description' => 'required|string',
            'category_id' => 'required|uuid',
            'base_price' => 'required_if:type,service|numeric',
            'time_start' => 'required_if:type,service|date_format:H:i',
            'time_end' => 'required_if:type,service|date_format:H:i|after:time_start',
            'portfolio_url' => 'sometimes|nullable|url',
            'experience_years' => 'required_if:type,service|integer|min:0',
            'province' => 'required_if:type,service|string',
            'regency' => 'required_if:type,service|string',
            'district' => 'required_if:type,service|string',    
            'village' => 'required_if:type,service|string',
            'address_details' => 'required_if:type,service|string',
            'status' => 'required_if:type,service|in:active,inactive',
            'images' => 'required|array|max:5',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'location' => 'required_if:type,service|array',
            'location.latitude' => 'required_if:type,service|numeric|between:-90,90',
            'location.longitude' => 'required_if:type,service|numeric|between:-180,180',
        ]);

        $data = $request->all();

        $uploadedImages = $request->file('images');

        $result = $this->postService->createServicePost($data, $uploadedImages);

        return response()->json($result, $result['code']);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid',
        ]);

        $result = $this->postService->deletePost($request->id);

        return response()->json($result, $result['code']);
    }

}
