<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public function reportTransaction(): HasOne
    {
        return $this->hasOne(ReportTransaction::class, 'transaction_id');
    }
}
