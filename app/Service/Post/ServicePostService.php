<?php
namespace App\Service\Post;

use App\Enum\ActiveOffEnum;
use App\Models\Post;
use App\Traits\ServiceResponse;
use Illuminate\Support\Facades\DB;

class ServicePostService
{

    use ServiceResponse;

    public function getAll()
    {
        $posts = Post::with([
            'category',
            'serviceDetail' => function ($query) {
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province, regency, district, village, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
            },
            'images',
        ])->get();

        return $this->successPayload($posts, 'service posts retrieved successfully');
    }

    public function createServicePostDetails(Post $newPost, array $data)
    {
        $longitude = $data['location']['longitude'];
        $latitude = $data['location']['latitude'];
        $portfolioUrl = $data['portfolio_url'] ?? null;
        $startTime = $data['time_start'] ?? null;
        $endTime = $data['time_end'] ?? null;
        
        $workingHours = $startTime && $endTime ? $startTime . ' - ' . $endTime : null;

        $newPost->serviceDetail()->create([
            'base_price' => $data['base_price'],
            'working_hours' => $workingHours,
            'portfolio_url' => $portfolioUrl,
            'experience_years' => $data['experience_years'],
            'province' => $data['province'],
            'regency' => $data['regency'],
            'district' => $data['district'],
            'village' => $data['village'],
            'address_details' => $data['address_details'],
            'location' => DB::raw("ST_SRID(POINT({$longitude}, {$latitude}), 4326)"),
            'status' => ActiveOffEnum::ACTIVE->value,
        ]);

        return $newPost->load([
            'serviceDetail' => function ($query) {
                $query->selectRaw('post_id, base_price, working_hours, portfolio_url, experience_years, status, province, regency, district, village, address_details, ST_X(location) as longitude, ST_Y(location) as latitude, created_at, updated_at');
            },
            'images',
        ]);
    }
}