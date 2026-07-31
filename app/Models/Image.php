<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Guarded([])]
class Image extends Model
{
    use HasUuids;

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
