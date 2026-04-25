<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update the role enum to include 'runner'
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['owner', 'admin', 'teller', 'runner'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['owner', 'admin', 'teller'])->change();
        });
    }
};
