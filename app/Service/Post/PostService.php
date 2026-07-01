<?php

namespace App\Service\Post;

use App\Enum\TypePostEnum;
use App\Models\Post;
use App\Models\User;
use App\Traits\ServiceResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostService
{
    use ServiceResponse;

    protected RequestPostService $requestPostService;

    protected OfferPostService $offerPostService;

    public function __construct(RequestPostService $requestPostService, OfferPostService $offerPostService)
    {
        $this->requestPostService = $requestPostService;
        $this->offerPostService = $offerPostService;
    }

    // TODO menambahkan logic untuk filter by category name
    public function getAllPosts()
    {
        $posts = Post::with([
            'category',
            'users',
            'users.photoProfile',
            // 'users.ktpPhoto',
            'requestDetail' => function ($query) {
                $query->selectRaw('post_id, min_price, max_price, deadline, method_service, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'requestDetail.province:id,name',
            'requestDetail.city:id,name',
            'requestDetail.district:id,name',
            'requestDetail.village:id,name',
            'offerDetail' => function ($query) {
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'offerDetail.province:id,name',
            'offerDetail.city:id,name',
            'offerDetail.district:id,name',
            'offerDetail.village:id,name',
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

    // TODO menambahkan logic untuk filter by category name
    public function getAllPostsWithRequestDetails()
    {
        $posts = Post::with([
            'category',
            'users',
            'users.photoProfile',
            // 'users.ktpPhoto',
            'requestDetail' => function ($query) {
                $query->selectRaw('post_id, min_price, max_price, deadline, method_service, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'requestDetail.province:id,name',
            'requestDetail.city:id,name',
            'requestDetail.district:id,name',
            'requestDetail.village:id,name',
            'images',
        ])->get();

        return $this->successPayload($posts, 'posts with request details retrieved successfully');
    }

    // TODO menambahkan logic untuk filter by category name
    public function getAllWithOfferDetails()
    {
        $posts = Post::with([
            'category',
            'users',
            'users.photoProfile',
            // 'users.ktpPhoto',
            'offerDetail' => function ($query) {
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'offerDetail.province:id,name',
            'offerDetail.city:id,name',
            'offerDetail.district:id,name',
            'offerDetail.village:id,name',
            'images',
        ])->get();

        return $this->successPayload($posts, 'posts with offer details retrieved successfully');
    }


    // TODO isikan validasi user sudah verified atau belum, jika belum maka tidak bisa membuat post
    public function createRequestPost(array $data, array $uploadedImages)
    {
        return DB::transaction(function () use ($data, $uploadedImages) {
            $typePost = TypePostEnum::from($data['type']);
            $isMutiple = $typePost === TypePostEnum::REQUEST ? false : true;
            $userId = auth('sanctum')->id();

            $user = User::where('id', $userId)->first();

            // if (!$user->email_verified_at) {
            //     return $this->errorPayload('user email is not verified', null, 403);
            // }

            $userHasSamePost = $user->posts()->where('type', TypePostEnum::REQUEST->value)
                ->whereHas('requestDetail', function ($query) {
                    $query->where('status', 'open');
                })
                ->where('title', $data['title'])
                ->exists();

            if ($userHasSamePost) {
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

    // TODO isikan validasi user sudah verified atau belum, jika belum maka tidak bisa membuat post
    public function createOfferPost(array $data, array $uploadedImages)
    {
        return DB::transaction(function () use ($data, $uploadedImages) {
            $typePost = TypePostEnum::from($data['type']);
            $isMutiple = $typePost === TypePostEnum::OFFER ? true : false;
            $userId = auth('sanctum')->id();

            $user = User::where('id', $userId)->first();

            // if (!$user->email_verified_at) {
            //     return $this->errorPayload('user email is not verified', null, 403);
            // }

            $userHasSamePost = $user->posts()->where('type', TypePostEnum::OFFER->value)
                ->whereHas('offerDetail', function ($query) {
                    $query->where('status', 'active');
                })
                ->where('title', $data['title'])
                ->exists();

            if ($userHasSamePost) {
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

            $post = $this->offerPostService->createOfferPostDetails($post, $data);

            return $this->successPayload($post, 'offer post created successfully', 201);
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
