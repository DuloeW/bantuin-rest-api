<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Transaction extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'final_price' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_price' => 'decimal:2',
        'deadline' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updated(function (Transaction $transaction) {
            if ($transaction->wasChanged('status') && in_array($transaction->status, ['cancelled', 'disputed'])) {
                if ($transaction->offer && $transaction->offer->status !== 'completed') {
                    $transaction->offer->update(['status' => 'completed']);
                }
            }
        });
    }

    public function reportTransaction(): HasOne
    {
        return $this->hasOne(ReportTransaction::class, 'transaction_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function escrow(): HasOne
    {
        return $this->hasOne(EscrowTransaction::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function helper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'helper_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function completionImages(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('type', 'completion');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(TransactionRevision::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'transaction_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function refundImages(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('type', 'refund');
    }
}

