<?php 

namespace App\Service\Post;

use App\Enum\OpenCloseEnum;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class RequestPostService
{
    public function createRequestPostDetails(Post $newPost, array $data)
    {
        $longitude = $data['location']['longitude'];
        $latitude = $data['location']['latitude'];

        $newPost->requestDetail()->create([
            'min_price' => $data['min_price'],
            'max_price' => $data['max_price'],
            'deadline' => $data['deadline'],
            'method_service' => $data['method_service'],
            'province_id' => $data['province_id'],
            'city_id' => $data['city_id'],
            'district_id' => $data['district_id'],
            'village_id' => $data['village_id'],
            'address_details' => $data['address_details'],
            'location' => DB::raw("ST_GeomFromText('POINT($latitude $longitude)', 4326)"),
            'published_until' => $data['published_until'],
            'status' => OpenCloseEnum::OPEN->value,
        ]);

        return $newPost->load([
            'requestDetail' => function ($query) {
                $query->selectRaw('post_id, min_price, max_price, deadline, method_service, province_id, city_id, district_id, village_id, address_details, status, ST_X(location) as latitude, ST_Y(location) as longitude,published_until, created_at');
            },
            'requestDetail.province',
            'requestDetail.city',
            'requestDetail.district',
            'requestDetail.village',
            'images',
        ]);
    }
}