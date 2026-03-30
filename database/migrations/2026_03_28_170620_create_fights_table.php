<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->string('fight_number');
            $table->enum('status', ['pending', 'open', 'closed', 'done', 'cancelled'])
                  ->default('pending');
            $table->enum('winner', ['meron', 'wala', 'draw', 'cancelled'])
                  ->nullable();
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fights');
    }
};
