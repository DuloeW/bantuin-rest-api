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

    public function updateRequestPostDetails(Post $post, array $data): Post
    {
        if (!$post->requestDetail) {
            return $post;
        }

        $updateData = [];

        if (isset($data['min_price']))       $updateData['min_price']       = $data['min_price'];
        if (isset($data['max_price']))       $updateData['max_price']       = $data['max_price'];
        if (isset($data['deadline']))        $updateData['deadline']        = $data['deadline'];
        if (isset($data['method_service']))  $updateData['method_service']  = $data['method_service'];
        if (isset($data['province_id']))     $updateData['province_id']     = $data['province_id'];
        if (isset($data['city_id']))         $updateData['city_id']         = $data['city_id'];
        if (isset($data['district_id']))     $updateData['district_id']     = $data['district_id'];
        if (isset($data['village_id']))      $updateData['village_id']      = $data['village_id'];
        if (isset($data['address_details'])) $updateData['address_details'] = $data['address_details'];
        if (isset($data['published_until'])) $updateData['published_until'] = $data['published_until'];

        if (isset($data['location']['latitude']) && isset($data['location']['longitude'])) {
            $lat = $data['location']['latitude'];
            $lon = $data['location']['longitude'];
            $updateData['location'] = DB::raw("ST_GeomFromText('POINT($lat $lon)', 4326)");
        }

        if (!empty($updateData)) {
            $post->requestDetail()->update($updateData);
        }

        return $post->load([
            'requestDetail' => function ($query) {
                $query->selectRaw('post_id, min_price, max_price, deadline, method_service, province_id, city_id, district_id, village_id, address_details, status, ST_X(location) as latitude, ST_Y(location) as longitude, published_until, created_at');
            },
            'requestDetail.province',
            'requestDetail.city',
            'requestDetail.district',
            'requestDetail.village',
            'images',
        ]);
    }
}