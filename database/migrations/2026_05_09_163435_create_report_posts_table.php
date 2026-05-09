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
        Schema::create('report_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')
                ->constrained('posts')
                ->cascadeOnDelete();
            $table->foreignUuid('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('reason_category');
            $table->text('description')->nullable();
            $table->string('evidence_file')->nullable();
            $table->enum('status', ['pending', 'investigating', 'resolved'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_posts');
    }
};
