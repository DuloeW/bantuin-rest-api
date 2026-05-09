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
        Schema::create('service_posts', function (Blueprint $table) {
            $table->foreignUuid('post_id')
                ->constrained()
                ->cascadeOnDelete()
                ->primary();
            $table->decimal('base_price', 15, 2);
            $table->string('working_hours');
            $table->string('portfolio_url')->nullable();
            $table->integer('experience_years')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_posts');
    }
};
