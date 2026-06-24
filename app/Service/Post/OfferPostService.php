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
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province, regency, district, village, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
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

        $workingHours = $startTime && $endTime ? $startTime . ' - ' . $endTime : null;

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
            'location' => DB::raw("ST_GeomFromText('POINT($longitude $latitude)', 4326)"),
            'status' => ActiveOffEnum::formatToEnum($data['status'])->value,
        ]);

        return $newPost->load([
            'offerDetail' => function ($query) {
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province_id, city_id, district_id, village_id, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
            },
            'offerDetail.province',
            'offerDetail.city',
            'offerDetail.district',
            'offerDetail.village',
            'images',
        ]);
    }
}
