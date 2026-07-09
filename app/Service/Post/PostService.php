<?php
namespace App\Service\Post;

use App\Enum\TypePostEnum;
use App\Models\Post;
use App\Models\User;
use App\Traits\ServiceResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ReportPost;
use App\Models\Image;

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

    public function getMyJobs(string $userId, ?string $type)
    {
        $query = Post::where('user_id', '!=', $userId);

        if ($type === 'request') {
            $query->where('type', 'request')
                ->whereHas('offers', function ($q) use ($userId) {
                    $q->where('helper_id', $userId);
                })
                ->with([
                    'category',
                    'users',
                    'users.photoProfile',
                    'images',
                    'offers' => function ($q) use ($userId) {
                        $q->where('helper_id', $userId);
                    },
                    'requestDetail' => function ($q) {
                        $q->selectRaw('post_id, min_price, max_price, deadline, method_service, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
                    },
                    'requestDetail.province:id,name',
                    'requestDetail.city:id,name',
                    'requestDetail.district:id,name',
                    'requestDetail.village:id,name',
                ]);
        } elseif ($type === 'offer') {
            $query->where('type', 'offer')
                ->whereHas('offers', function ($q) use ($userId) {
                    $q->where('requester_id', $userId);
                })
                ->with([
                    'category',
                    'users',
                    'users.photoProfile',
                    'images',
                    'offers' => function ($q) use ($userId) {
                        $q->where('requester_id', $userId);
                    },
                    'offerDetail' => function ($q) {
                        $q->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
                    },
                    'offerDetail.province:id,name',
                    'offerDetail.city:id,name',
                    'offerDetail.district:id,name',
                    'offerDetail.village:id,name',
                ]);
        } else {
            // type is null, load both request and offer interactions
            $query->where(function ($q) use ($userId) {
                $q->where(function ($sq1) use ($userId) {
                    $sq1->where('type', 'request')
                        ->whereHas('offers', function ($o) use ($userId) {
                            $o->where('helper_id', $userId);
                        });
                })->orWhere(function ($sq2) use ($userId) {
                    $sq2->where('type', 'offer')
                        ->whereHas('offers', function ($o) use ($userId) {
                            $o->where('requester_id', $userId);
                        });
                });
            })->with([
                'category',
                'users',
                'users.photoProfile',
                'images',
                'offers' => function ($q) use ($userId) {
                    $q->where('helper_id', $userId)
                      ->orWhere('requester_id', $userId);
                },
                'requestDetail' => function ($q) {
                    $q->selectRaw('post_id, min_price, max_price, deadline, method_service, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
                },
                'requestDetail.province:id,name',
                'requestDetail.city:id,name',
                'requestDetail.district:id,name',
                'requestDetail.village:id,name',
                'offerDetail' => function ($q) {
                    $q->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
                },
                'offerDetail.province:id,name',
                'offerDetail.city:id,name',
                'offerDetail.district:id,name',
                'offerDetail.village:id,name',
            ]);
        }

        $posts = $query->get();

        return $this->successPayload($posts, 'interacted user posts retrieved successfully');
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

    public function searchPost(array $filters)
    {
        $query = Post::with([
            'category',
            'users',
            'users.photoProfile',
            'requestDetail' => function ($q) {
                $q->selectRaw('post_id, min_price, max_price, deadline, method_service, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'requestDetail.province:id,name',
            'requestDetail.city:id,name',
            'requestDetail.district:id,name',
            'requestDetail.village:id,name',
            'offerDetail' => function ($q) {
                $q->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'offerDetail.province:id,name',
            'offerDetail.city:id,name',
            'offerDetail.district:id,name',
            'offerDetail.village:id,name',
            'images',
        ]);

        // Search by keyword (title or description)
        if (!empty($filters['query'])) {
            $keyword = $filters['query'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // Filter by location (province, city, district, village)
        if (!empty($filters['province_id'])) {
            $provinceVal = $filters['province_id'];
            $query->where(function ($q) use ($provinceVal) {
                $q->whereHas('requestDetail', function ($sq) use ($provinceVal) {
                    if (is_numeric($provinceVal)) {
                        $sq->where('province_id', $provinceVal);
                    } else {
                        $sq->whereHas('province', function ($ssq) use ($provinceVal) {
                            $ssq->where('name', 'like', "%{$provinceVal}%");
                        });
                    }
                })->orWhereHas('offerDetail', function ($sq) use ($provinceVal) {
                    if (is_numeric($provinceVal)) {
                        $sq->where('province_id', $provinceVal);
                    } else {
                        $sq->whereHas('province', function ($ssq) use ($provinceVal) {
                            $ssq->where('name', 'like', "%{$provinceVal}%");
                        });
                    }
                });
            });
        }

        if (!empty($filters['city_id'])) {
            $cityVal = $filters['city_id'];
            $query->where(function ($q) use ($cityVal) {
                $q->whereHas('requestDetail', function ($sq) use ($cityVal) {
                    if (is_numeric($cityVal)) {
                        $sq->where('city_id', $cityVal);
                    } else {
                        $sq->whereHas('city', function ($ssq) use ($cityVal) {
                            $ssq->where('name', 'like', "%{$cityVal}%");
                        });
                    }
                })->orWhereHas('offerDetail', function ($sq) use ($cityVal) {
                    if (is_numeric($cityVal)) {
                        $sq->where('city_id', $cityVal);
                    } else {
                        $sq->whereHas('city', function ($ssq) use ($cityVal) {
                            $ssq->where('name', 'like', "%{$cityVal}%");
                        });
                    }
                });
            });
        }

        if (!empty($filters['district_id'])) {
            $districtVal = $filters['district_id'];
            $query->where(function ($q) use ($districtVal) {
                $q->whereHas('requestDetail', function ($sq) use ($districtVal) {
                    if (is_numeric($districtVal)) {
                        $sq->where('district_id', $districtVal);
                    } else {
                        $sq->whereHas('district', function ($ssq) use ($districtVal) {
                            $ssq->where('name', 'like', "%{$districtVal}%");
                        });
                    }
                })->orWhereHas('offerDetail', function ($sq) use ($districtVal) {
                    if (is_numeric($districtVal)) {
                        $sq->where('district_id', $districtVal);
                    } else {
                        $sq->whereHas('district', function ($ssq) use ($districtVal) {
                            $ssq->where('name', 'like', "%{$districtVal}%");
                        });
                    }
                });
            });
        }

        if (!empty($filters['village_id'])) {
            $villageVal = $filters['village_id'];
            $query->where(function ($q) use ($villageVal) {
                $q->whereHas('requestDetail', function ($sq) use ($villageVal) {
                    if (is_numeric($villageVal)) {
                        $sq->where('village_id', $villageVal);
                    } else {
                        $sq->whereHas('village', function ($ssq) use ($villageVal) {
                            $ssq->where('name', 'like', "%{$villageVal}%");
                        });
                    }
                })->orWhereHas('offerDetail', function ($sq) use ($villageVal) {
                    if (is_numeric($villageVal)) {
                        $sq->where('village_id', $villageVal);
                    } else {
                        $sq->whereHas('village', function ($ssq) use ($villageVal) {
                            $ssq->where('name', 'like', "%{$villageVal}%");
                        });
                    }
                });
            });
        }

        // Filter by price
        if (isset($filters['min_price'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('requestDetail', function ($sq) use ($filters) {
                    $sq->where('max_price', '>=', $filters['min_price']);
                })->orWhereHas('offerDetail', function ($sq) use ($filters) {
                    $sq->where('base_price', '>=', $filters['min_price']);
                });
            });
        }

        if (isset($filters['max_price'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('requestDetail', function ($sq) use ($filters) {
                    $sq->where('min_price', '<=', $filters['max_price']);
                })->orWhereHas('offerDetail', function ($sq) use ($filters) {
                    $sq->where('base_price', '<=', $filters['max_price']);
                });
            });
        }

        // Filter by post type (request or service/offer)
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $catVal = $filters['category_id'];
            if (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/i', $catVal)) {
                $query->where('category_id', $catVal);
            } else {
                $query->whereHas('category', function ($sq) use ($catVal) {
                    $sq->where('title', 'like', "%{$catVal}%");
                });
            }
        }

        $posts = $query->get();

        return $this->successPayload($posts, 'posts searched and filtered successfully');
    }

    public function getNearMePosts(array $data)
    {
        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];
        $maxDistanceKm = isset($data['radius']) ? (float) $data['radius'] : 50.0; // default 50 km

        $posts = Post::with([
            'category',
            'users',
            'users.photoProfile',
            'requestDetail' => function ($q) {
                $q->selectRaw('post_id, min_price, max_price, deadline, method_service, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'requestDetail.province:id,name',
            'requestDetail.city:id,name',
            'requestDetail.district:id,name',
            'requestDetail.village:id,name',
            'offerDetail' => function ($q) {
                $q->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'offerDetail.province:id,name',
            'offerDetail.city:id,name',
            'offerDetail.district:id,name',
            'offerDetail.village:id,name',
            'images',
        ])
        ->select('posts.*')
        ->leftJoin('request_posts', 'request_posts.post_id', '=', 'posts.id')
        ->leftJoin('service_posts', 'service_posts.post_id', '=', 'posts.id')
        ->selectRaw("
            ST_Distance_Sphere(
                COALESCE(request_posts.location, service_posts.location),
                ST_GeomFromText(?, 4326)
            ) as distance_meters
        ", ["POINT($latitude $longitude)"])
        ->where(function($q) {
            $q->whereNotNull('request_posts.location')
              ->orWhereNotNull('service_posts.location');
        })
        ->having('distance_meters', '<=', $maxDistanceKm * 1000)
        ->orderBy('distance_meters', 'asc')
        ->get();

        // Tambahkan field distance (dalam km) ke setiap post
        $posts = $posts->map(function ($post) {
            $post->distance = round($post->distance_meters / 1000, 2);
            return $post;
        });

        return $this->successPayload($posts, 'nearest posts retrieved successfully');
    }

    public function getPostById(string $id)
    {
        $post = Post::with([
            'category',
            'users',
            'users.photoProfile',
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
        ])->find($id);

        if (!$post) {
            return $this->errorPayload('post not found', [], 404);
        }

        return $this->successPayload($post, 'post retrieved successfully');
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

    public function reportPost(string $postId, string $reporterId, array $data, array $uploadedImages = [])
    {
        $post = Post::find($postId);
        if (!$post) {
            return $this->errorPayload('post not found', [], 404);
        }

        $report = ReportPost::create([
            'post_id' => $postId,
            'reporter_id' => $reporterId,
            'reason_category' => $data['reason_category'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        foreach ($uploadedImages as $imageFile) {
            $path = $imageFile->store('evidences/posts', 'public');
            $report->images()->create([
                'url' => $path,
                'file_name' => $imageFile->getClientOriginalName(),
                'file_type' => $imageFile->getClientMimeType(),
            ]);
        }

        return $this->successPayload($report, 'post reported successfully', 201);
    }
}