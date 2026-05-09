<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTransaction extends Model
{
    use HasFactory;

   
    protected $table = 'Reports_Transaction';

    
    protected $fillable = [
        'transaction_id', 
        'reason_category',
        'status',
        'description',
        'evidence_files',
    ];

   
    protected $casts = [
        // SANGAT PENTING untuk Filament: Karena tadi di FileUpload kita pakai ->multiple(),
        // Filament akan menyimpan path file dalam bentuk array/JSON di database.
        'evidence_files' => 'array', 
    ];

    // Relasi ke model Transaction (Satu laporan dimiliki oleh satu transaksi)
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}