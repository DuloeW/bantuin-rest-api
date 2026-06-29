<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'final_price'  => 'decimal:2',
        'admin_fee'    => 'decimal:2',
        'total_price'  => 'decimal:2',
        'deadline'     => 'datetime',
        'started_at'   => 'datetime',
        'finished_at'  => 'datetime',
    ];

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
}
