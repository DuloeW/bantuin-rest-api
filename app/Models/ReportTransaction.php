<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReportTransaction extends Model
{
    use HasUuids;
    protected $guarded = [];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reported()
    {
        return $this->belongsTo(User::class, 'reported_id');
    }
}
