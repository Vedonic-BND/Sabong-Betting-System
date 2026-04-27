<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_requests', function (Blueprint $table) {
            $table->enum('request_type', ['assistance', 'need_cash', 'collect_cash', 'other'])
                ->nullable()
                ->after('reason');
            $table->text('custom_message')->nullable()->after('request_type');
        });
    }

    public function down(): void
    {
        Schema::table('cash_requests', function (Blueprint $table) {
            $table->dropColumn(['request_type', 'custom_message']);
        });
    }
};
