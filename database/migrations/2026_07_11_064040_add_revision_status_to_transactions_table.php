<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tambah status 'revision' pada tabel transactions.
     * Status ini digunakan ketika helper meng-accept permintaan revisi dari requester.
     *
     * Flow status revisi:
     * pending_revision → (helper accept) → revision → (helper selesai) → pending_approval
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'on_progress',
                'pending_approval',
                'pending_revision',
                'revision',
                'pending_refund',
                'completed',
                'disputed',
                'cancelled',
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'on_progress',
                'pending_approval',
                'pending_revision',
                'pending_refund',
                'completed',
                'disputed',
                'cancelled',
            ])->default('pending')->change();
        });
    }
};
