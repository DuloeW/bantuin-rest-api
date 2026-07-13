<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_transactions', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('description');
            $table->char('resolved_by', 36)->nullable()->after('admin_notes');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');
        });
    }

    public function down(): void
    {
        Schema::table('report_transactions', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn(['admin_notes', 'resolved_by', 'resolved_at']);
        });
    }
};

