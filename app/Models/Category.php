<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class Category extends Model
{
    use HasUuids;

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
