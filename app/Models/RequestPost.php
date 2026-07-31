<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

#[Guarded([])]
#[Hidden([
    'location',
    'longitude',
    'latitude',
    'province_id',
    'city_id',
    'district_id',
    'village_id',
])]
class RequestPost extends Model
{
    protected $primaryKey = 'post_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $appends = ['location_coordinate'];

    protected $casts = [
        'deadline' => 'datetime',
        'published_at' => 'datetime',
        'published_until' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function getLocationCoordinateAttribute()
    {
        $lat = $this->attributes['latitude'] ?? null;
        $lon = $this->attributes['longitude'] ?? null;

        if (($lat === null || $lon === null) && isset($this->attributes['location'])) {
            // parse POINT(lat lon)
            $loc = $this->attributes['location'];
            if (preg_match('/POINT\(([-0-9.]+) ([-0-9.]+)\)/', $loc, $m)) {
                $lat = $m[1];
                $lon = $m[2];
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
