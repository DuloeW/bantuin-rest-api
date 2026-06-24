<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

#[Guarded([])]
class RequestPost extends Model
{
    protected $appends = ['location_coordinate'];
    protected $hidden = ['location', 'longitude', 'latitude', 'province_id', 'city_id', 'district_id', 'village_id'];

    protected $casts = [
        'deadline' => 'datetime',
        'published_at' => 'datetime',
        'published_until' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function getLocationCoordinateAttribute()
    {
        $lat = $this->attributes['latitude'] ?? null;
        $lon = $this->attributes['longitude'] ?? null;

        if (($lat === null || $lon === null) && isset($this->attributes['location'])) {
            // parse POINT(lon lat)
            $loc = $this->attributes['location'];
            if (preg_match('/POINT\(([-0-9.]+) ([-0-9.]+)\)/', $loc, $m)) {
                $lon = $m[1];
                $lat = $m[2];
            }
        }

        if ($lat === null || $lon === null) {
            return null;
        }

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lon,
        ];
    }
}
