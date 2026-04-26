<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'slug'])]
class Category extends Model
{
    use HasUuids;

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
