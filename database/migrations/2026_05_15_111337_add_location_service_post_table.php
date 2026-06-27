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
        Schema::table('service_posts', function (Blueprint $table) {
            $table->string('province');
            $table->string('regency');
            $table->string('district');
            $table->string('village');
            $table->text('address_details');
            $table->geography('location', subtype: 'point', srid: 4326);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_posts', function (Blueprint $table) {
            $table->dropColumn('province');
            $table->dropColumn('regency');
            $table->dropColumn('district');
            $table->dropColumn('village');
            $table->dropColumn('address_details');
            $table->dropColumn('location');
        });
    }
};
