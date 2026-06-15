<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class Offer extends Model
{
    use HasUuids;
    
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function helper()
    {
        return $this->belongsTo(User::class, 'helper_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
