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
            $table->id();
            $table->foreignId('post_id')
                    ->constrained('posts')
                    ->cascadeOnDelete();
            $table->foreignId('offer_id')
                    ->nullable()
                    ->constrained('offers')
                    ->nullOnDelete();
            $table->foreignId('requester_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            $table->foreignId('helper_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            $table->decimal('final_price', 15, 2);
            $table->enum('status', ['on_progress', 'completed', 'disputed', 'cancelled'])->default('on_progress');
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
