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
        Schema::table('request_posts', function (Blueprint $table) {
            $table->foreignId('province_id')
                ->nullable()
                ->constrained('indonesia_provinces');

            $table->foreignId('city_id')
                ->nullable()
                ->constrained('indonesia_cities');

            $table->foreignId('district_id')
                ->nullable()
                ->constrained('indonesia_districts');

            $table->foreignId('village_id')
                ->nullable()
                ->constrained('indonesia_villages');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_posts', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['village_id']);
        });
    }
};
