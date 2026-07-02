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
        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')
                ->constrained('transactions')
                ->cascadeOnDelete();
            $table->foreignUuid('payment_id')
                ->constrained('payments')
                ->cascadeOnDelete();
            $table->decimal('held_amount', 15, 2);   // Total yang ditahan (= total_price)
            $table->decimal('fee_amount', 15, 2);    // Fee platform (= admin_fee)
            $table->decimal('net_amount', 15, 2);    // Yang akan diterima helper (= final_price)
            $table->enum('status', [
                'held',       // Dana ditahan di escrow
                'released',   // Dana dilepas ke helper
                'refunded',   // Dana dikembalikan ke requester
                'disputed',   // Dalam sengketa, menunggu keputusan admin
            ])->default('held');
            $table->timestamp('held_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('release_notes')->nullable();   // Catatan saat release
            $table->text('dispute_reason')->nullable();  // Alasan sengketa
            $table->foreignUuid('resolved_by')           // Admin yang memediasi
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escrow_transactions');
    }
};
