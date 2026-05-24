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
            'province' => $data['province'],
            'regency' => $data['regency'],
            'district' => $data['district'],
            'village' => $data['village'],
            'address_details' => $data['address_details'],
            'location' => DB::raw("ST_GeomFromText('POINT($longitude $latitude)', 4326)"),
            'published_until' => $data['published_until'],
            'status' => OpenCloseEnum::OPEN->value,
        ]);

        return $newPost->load([
            'requestDetail' => function ($query) {
                $query->selectRaw('post_id, min_price, max_price, deadline, method_service, province, regency, district, village, address_details, status, ST_X(location) as longitude, ST_Y(location) as latitude,published_until, created_at');
            },
            'images',
        ]);
    }
}