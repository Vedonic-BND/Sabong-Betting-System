<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Owner',
            'username' => 'owner',
            'password' => Hash::make('owner1234'),
            'role'     => 'owner',
        ]);
    }
}
