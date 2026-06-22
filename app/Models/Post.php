<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


#[Guarded([])]
class Post extends Model
{
    use HasUuids, HasFactory;
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function offerDetail()
    {
        return $this->hasOne(OfferPost::class);
    }

    public function requestDetail()
    {
        return $this->hasOne(RequestPost::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
}
