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
        Schema::table('payments', function (Blueprint $table) {
            // Snap token dari Midtrans untuk redirect ke payment page
            $table->string('snap_token')->nullable()->after('payment_method');
            // Order ID unik yang dikirim ke Midtrans
            $table->string('midtrans_order_id')->nullable()->unique()->after('snap_token');
            // Nomor VA yang digenerate Midtrans
            $table->string('va_number')->nullable()->after('midtrans_order_id');
            // Bank VA yang dipilih user (bca, bni, bri, dll)
            $table->string('bank')->nullable()->after('va_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['snap_token', 'midtrans_order_id', 'va_number', 'bank']);
        });
    }
};
