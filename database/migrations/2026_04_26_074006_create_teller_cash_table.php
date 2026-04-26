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
        Schema::create('teller_cash', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teller_id')->constrained('users')->onDelete('cascade');
            $table->decimal('total_cash_in', 12, 2)->default(0);
            $table->decimal('total_paid_out', 12, 2)->default(0);
            $table->decimal('on_hand_cash', 12, 2)->default(0);
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
            $table->unique('teller_id');
            $table->index('on_hand_cash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teller_cash');
    }
};
