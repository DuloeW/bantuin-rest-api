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
            $table->timestamp('published_at')->nullable()->after('location');
            $table->timestamp('published_until')->nullable()->after('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_posts', function (Blueprint $table) {
            $table->dropColumn(['published_at', 'published_until']);    
        });
    }
};