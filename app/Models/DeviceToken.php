<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'device_token', 'device_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


