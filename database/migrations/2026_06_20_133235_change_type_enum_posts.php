<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('posts', function (Blueprint $table) {
                $table->string('type')->change();
            });
            DB::statement("UPDATE posts SET type = 'offer' WHERE type = 'service'");
        } else {
            DB::statement("ALTER TABLE posts MODIFY type ENUM('request', 'service', 'offer') NOT NULL");
            DB::statement("UPDATE posts SET type = 'offer' WHERE type = 'service'");
            DB::statement("ALTER TABLE posts MODIFY type ENUM('request', 'offer') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE posts SET type = 'service' WHERE type = 'offer'");
        } else {
            DB::statement("ALTER TABLE posts MODIFY type ENUM('request', 'offer', 'service') NOT NULL");
            DB::statement("UPDATE posts SET type = 'service' WHERE type = 'offer'");
            DB::statement("ALTER TABLE posts MODIFY type ENUM('request', 'service') NOT NULL");
        }
    }
};
