<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class Skill extends Model
{

    use HasUuids;

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
