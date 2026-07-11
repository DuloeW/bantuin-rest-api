<?php

namespace App\Service\Post;

use App\Enum\ActiveOffEnum;
use App\Models\Post;
use App\Traits\ServiceResponse;
use Illuminate\Support\Facades\DB;
use Laravolt\Indonesia\IndonesiaService;

class OfferPostService
{
    use ServiceResponse;

    protected IndonesiaService $indonesiaService;

    public function __construct(IndonesiaService $indonesiaService)
    {
        $this->indonesiaService = $indonesiaService;
    }

    public function getAll()
    {
        $posts = Post::with([
            'category',
            'offerDetail' => function ($query) {
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province, regency, district, village, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'images',
        ])->get();

        return $this->successPayload($posts, 'offer posts retrieved successfully');
    }

    public function createOfferPostDetails(Post $newPost, array $data)
    {
        $longitude = $data['location']['longitude'];
        $latitude = $data['location']['latitude'];
        $portfolioUrl = $data['portfolio_url'] ?? null;
        $startTime = $data['time_start'] ?? null;
        $endTime = $data['time_end'] ?? null;

        $workingHours = $startTime && $endTime ? $startTime.' - '.$endTime : null;

        $newPost->offerDetail()->create([
            'base_price' => $data['base_price'],
            'working_hours' => $workingHours,
            'portfolio_url' => $portfolioUrl,
            'experience_years' => $data['experience_years'],
            'province_id' => $data['province_id'],
            'city_id' => $data['city_id'],
            'district_id' => $data['district_id'],
            'village_id' => $data['village_id'],
            'address_details' => $data['address_details'],
            'location' => DB::raw("ST_GeomFromText('POINT($latitude $longitude)', 4326)"),
            'status' => ActiveOffEnum::formatToEnum($data['status'])->value,
        ]);

        return $newPost->load([
            'offerDetail' => function ($query) {
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'offerDetail.province',
            'offerDetail.city',
            'offerDetail.district',
            'offerDetail.village',
            'images',
        ]);
    }

    public function updateOfferPostDetails(Post $post, array $data): Post
    {
        if (!$post->offerDetail) {
            return $post;
        }

        $updateData = [];

        if (isset($data['base_price']))      $updateData['base_price']       = $data['base_price'];
        if (isset($data['portfolio_url']))   $updateData['portfolio_url']    = $data['portfolio_url'];
        if (isset($data['experience_years'])) $updateData['experience_years'] = $data['experience_years'];
        if (isset($data['province_id']))     $updateData['province_id']      = $data['province_id'];
        if (isset($data['city_id']))         $updateData['city_id']          = $data['city_id'];
        if (isset($data['district_id']))     $updateData['district_id']      = $data['district_id'];
        if (isset($data['village_id']))      $updateData['village_id']       = $data['village_id'];
        if (isset($data['address_details'])) $updateData['address_details']  = $data['address_details'];
        if (isset($data['status']))          $updateData['status']           = ActiveOffEnum::formatToEnum($data['status'])->value;

        if (isset($data['time_start']) && isset($data['time_end'])) {
            $updateData['working_hours'] = $data['time_start'] . ' - ' . $data['time_end'];
        }

        if (isset($data['location']['latitude']) && isset($data['location']['longitude'])) {
            $lat = $data['location']['latitude'];
            $lon = $data['location']['longitude'];
            $updateData['location'] = DB::raw("ST_GeomFromText('POINT($lat $lon)', 4326)");
        }

        if (!empty($updateData)) {
            $post->offerDetail()->update($updateData);
        }

        return $post->load([
            'offerDetail' => function ($query) {
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as latitude, ST_Y(location) as longitude, created_at, updated_at');
            },
            'offerDetail.province',
            'offerDetail.city',
            'offerDetail.district',
            'offerDetail.village',
            'images',
        ]);
    }
}
