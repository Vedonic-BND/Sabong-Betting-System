<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('runner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['cash_in', 'cash_out']);
            $table->decimal('amount', 10, 2);
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('teller_id');
            $table->index('runner_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_requests');
    }
};
