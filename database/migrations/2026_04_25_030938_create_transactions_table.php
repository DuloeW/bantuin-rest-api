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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')
                    ->constrained('posts')
                    ->cascadeOnDelete();
            $table->foreignUuid('offer_id')
                    ->nullable()
                    ->constrained('offers')
                    ->nullOnDelete();
            $table->foreignUuid('requester_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            $table->foreignUuid('helper_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            $table->decimal('final_price', 15, 2);
            $table->enum('status', ['pending', 'on_progress', 'completed', 'disputed', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
