<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Guarded([])]
#[Hidden(['pivot'])]
class Skill extends Model
{

    use HasFactory, HasUuids;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'skill_users', 'skill_id', 'user_id');
    }
}
