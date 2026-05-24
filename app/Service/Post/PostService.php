<?php
namespace App\Service\Post;

use App\Enum\TypePostEnum;
use App\Models\Post;
use App\Models\User;
use App\Traits\ServiceResponse;
use Illuminate\Support\Facades\DB;

class PostService
{
    use ServiceResponse;

    protected RequestPostService $requestPostService;
    protected ServicePostService $servicePostService;

    public function __construct(RequestPostService $requestPostService, ServicePostService $servicePostService)
    {
        $this->requestPostService = $requestPostService;
        $this->servicePostService = $servicePostService;
    }


    public function getAllPosts()
    {   
        $posts = Post::with([
            'category',
            'requestDetail' => function ($query) {
                $query->selectRaw('post_id, min_price, max_price, deadline, method_service, status, province, regency, district, village, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
            },
            'serviceDetail' => function ($query) {
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province, regency, district, village, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
            },
            'images',
        ])->get();

        return $this->successPayload($posts, 'posts retrieved successfully');
    }

    public function getTotalUserPosts()
    {
        $user = auth('sanctum')->user();
        $postCount = Post::where('user_id', $user->id)->count(); 

        return $this->successPayload(['count' => $postCount], 'total user posts retrieved successfully');
    }

    public function getAllPostsWithRequestDetails()
    {
        $posts = Post::with([
            'category',
            'requestDetail' => function ($query) {
                $query->selectRaw('post_id, min_price, max_price, deadline, method_service, status, province, regency, district, village, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
            },
            'images',
        ])->get();

        return $this->successPayload($posts, 'posts with request details retrieved successfully');
    }

    public function getAllPostsWithServiceDetails()
    {
        $posts = Post::with([
            'category',
            'serviceDetail' => function ($query) {
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province, regency, district, village, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
            },
            'images',
        ])->get();

        return $this->successPayload($posts, 'posts with service details retrieved successfully');
    }


    public function createRequestPost(array $data, array $uploadedImages)
    {
        return DB::transaction(function () use ($data, $uploadedImages) {
            $typePost = TypePostEnum::from($data['type']);
            $isMutiple = $typePost === TypePostEnum::REQUEST ? false : true;
            $userId = auth('sanctum')->id();

            $user = User::where('id', $userId)->first();

            $userHasSamePost = $user->posts()->where('type', TypePostEnum::REQUEST->value)
                ->whereHas('requestDetail', function ($query) {
                    $query->where('status', 'open');
                })
                ->where('title', $data['title'])
                ->exists();

            if($userHasSamePost) {
                return $this->errorPayload('post title already exists', null, 422);
            }

            $post = Post::create([
                'user_id' => $userId,
                'title' => $data['title'],
                'type' => $typePost->value,
                'description' => $data['description'],
                'is_multiple' => $isMutiple,
                'category_id' => $data['category_id'],
            ]);

            $this->uploadImages($uploadedImages, $post);

            $post = $this->requestPostService->createRequestPostDetails($post, $data);
            return $this->successPayload($post, 'request post created successfully', 201);
        });
    }

    public function createServicePost(array $data, array $uploadedImages)
    {
        return DB::transaction(function () use ($data, $uploadedImages) {
            $typePost = TypePostEnum::from($data['type']);
            $isMutiple = $typePost === TypePostEnum::SERVICE ? true : false;
            $userId = auth('sanctum')->id();

            $user = User::where('id', $userId)->first();

            $userHasSamePost = $user->posts()->where('type', TypePostEnum::SERVICE->value)
                ->whereHas('serviceDetail', function ($query) {
                    $query->where('status', 'active');
                })
                ->where('title', $data['title'])
                ->exists();

            if($userHasSamePost) {
                return $this->errorPayload('post title already exists', null, 422);
            }

            $post = Post::create([
                'user_id' => $userId,
                'title' => $data['title'],
                'type' => $typePost->value,
                'description' => $data['description'],
                'is_multiple' => $isMutiple,
                'category_id' => $data['category_id'],
            ]);

            $this->uploadImages($uploadedImages, $post);

            $post = $this->servicePostService->createServicePostDetails($post, $data);
            return $this->successPayload($post, 'service post created successfully', 201);
        });
    }

    public function deletePost(string $id)
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return $this->successPayload(null, 'post deleted successfully');
    }

    private function uploadImages(array $uploadedImages, Post $post)
    {
        foreach ($uploadedImages as $imageFile) {
            $path = $imageFile->store('posts', 'public');

            $post->images()->create([
                'url' => $path,
                'file_name' => $imageFile->getClientOriginalName(), 
                'file_type' => $imageFile->getClientMimeType(),
            ]);
        }
    }
}