<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class RequestPost extends Model
{
    protected $appends = ['location_coordinate'];
    protected $hidden = ['location', 'longitude', 'latitude'];

    public function post()
    {
        return $this->belongsTo(Post::class);
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
