<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('bank_code');       // bca, bni, bri, mandiri, dll
            $table->string('bank_name');       // Nama tampilan: "BCA", "BNI", dll
            $table->string('account_number');  // Nomor rekening
            $table->string('account_name');    // Nama pemilik rekening
            $table->boolean('is_primary')->default(false);  // Rekening utama
            $table->boolean('is_verified')->default(false); // Sudah diverifikasi via Midtrans Iris
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
