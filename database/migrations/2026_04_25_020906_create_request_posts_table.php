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
        Schema::create('request_posts', function (Blueprint $table) {
            $table->foreignUuid('post_id')
                ->constrained()
                ->cascadeOnDelete()
                ->primary();
            $table->decimal('min_price', 15, 2);
            $table->decimal('max_price', 15, 2);
            $table->dateTime('deadline');
            $table->string('method_service');
            $table->string('province');
            $table->string('regency');
            $table->string('district');
            $table->string('village');
            $table->text('address_details');
            $table->geography('location', subtype: 'point', srid: 4326);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_posts');
    }
};
