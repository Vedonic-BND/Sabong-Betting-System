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
        Schema::table('fights', function (Blueprint $table) {
            if (!Schema::hasColumn('fights', 'meron_status')) {
                $table->enum('meron_status', ['open', 'closed'])->default('open')->after('status');
            }
            if (!Schema::hasColumn('fights', 'wala_status')) {
                $table->enum('wala_status', ['open', 'closed'])->default('open')->after('meron_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fights', function (Blueprint $table) {
            if (Schema::hasColumn('fights', 'meron_status')) {
                $table->dropColumn('meron_status');
            }
            if (Schema::hasColumn('fights', 'wala_status')) {
                $table->dropColumn('wala_status');
            }
        });
    }
};
