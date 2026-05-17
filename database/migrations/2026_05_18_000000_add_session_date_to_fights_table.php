<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fights', function (Blueprint $table) {
            // Add session_date to track which session/day the fight belongs to
            // This allows proper fight counter reset without causing duplicate numbers
            $table->date('session_date')->nullable()->after('fight_number');

            // Add index for efficient querying by session
            $table->index('session_date');
        });
    }

    public function down(): void
    {
        Schema::table('fights', function (Blueprint $table) {
            $table->dropIndex(['session_date']);
            $table->dropColumn('session_date');
        });
    }
};
