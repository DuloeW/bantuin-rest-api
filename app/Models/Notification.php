<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'title',
    'body',
    'data',
    'type',
    'is_read',
])]
class Notification extends Model
{
    use HasUuids;

    protected $casts = [
        'data' => 'json',
        'is_read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays(30))->orderByDesc('created_at');
    }
}

