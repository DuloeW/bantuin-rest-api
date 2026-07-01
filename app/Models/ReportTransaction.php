<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTransaction extends Model
{
    use HasUuids;
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        // SANGAT PENTING untuk Filament: Karena tadi di FileUpload kita pakai ->multiple(),
        // Filament akan menyimpan path file dalam bentuk array/JSON di database.
        'evidence_files' => 'array', 
    ];

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
