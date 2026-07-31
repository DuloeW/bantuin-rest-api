<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;


#[Guarded([])]
#[Hidden(['category_id', 'user_id'])]
class Post extends Model
{
    use HasUuids, HasFactory;

    protected $appends = ['avg_rating', 'completed_jobs_count'];

    public function getAvgRatingAttribute(): float
    {
        $avg = Review::whereHas('transaction.offer', function ($q) {
            $q->where('post_id', $this->id);
        })->avg('rating');

        return round((float) ($avg ?? 0), 1);
    }

    public function getCompletedJobsCountAttribute(): int
    {
        return Transaction::whereHas('offer', function ($q) {
            $q->where('post_id', $this->id);
        })->where('status', 'completed')->count();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function offerDetail(): HasOne
    {
        return $this->hasOne(OfferPost::class);
    }

    public function requestDetail(): HasOne
    {
        return $this->hasOne(RequestPost::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
