<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fight_id')->constrained('fights')->onDelete('restrict');
            $table->foreignId('teller_id')->constrained('users')->onDelete('restrict');
            $table->enum('side', ['meron', 'wala']);
            $table->decimal('amount', 10, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
