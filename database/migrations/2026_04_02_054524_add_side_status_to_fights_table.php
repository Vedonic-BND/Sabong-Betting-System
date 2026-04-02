<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fights', function (Blueprint $table) {
            $table->enum('meron_status', ['open', 'closed'])
                  ->default('open')
                  ->after('status');
            $table->enum('wala_status', ['open', 'closed'])
                  ->default('open')
                  ->after('meron_status');
        });
    }

    public function down(): void
    {
        Schema::table('fights', function (Blueprint $table) {
            $table->dropColumn(['meron_status', 'wala_status']);
        });
    }
};
