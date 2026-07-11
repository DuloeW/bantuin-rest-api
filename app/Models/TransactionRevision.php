<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TransactionRevision extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'completed_at' => 'datetime',
        'revision_deadline' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('type', 'tr-revision');
    }
}
