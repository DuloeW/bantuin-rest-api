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
                'partially_refunded',
                'refunded',
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
                'revision',
                'pending_refund',
                'completed',
                'disputed',
                'cancelled',
                'partially_refunded',
            ])->default('pending')->change();
        });
    }
};
