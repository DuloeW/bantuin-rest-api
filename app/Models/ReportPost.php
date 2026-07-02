<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReportPost extends Model
{
    use HasUuids;

    protected $guarded = [];
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

   
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
