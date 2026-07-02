<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscrowTransaction extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'held_amount'   => 'decimal:2',
        'fee_amount'    => 'decimal:2',
        'net_amount'    => 'decimal:2',
        'held_at'       => 'datetime',
        'released_at'   => 'datetime',
        'refunded_at'   => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isHeld(): bool
    {
        return $this->status === 'held';
    }

    public function isDisputed(): bool
    {
        return $this->status === 'disputed';
    }
}
