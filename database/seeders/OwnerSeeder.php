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

        User::create([
            'name'     => 'Teller 1',
            'username' => 'teller1',
            'password' => Hash::make('teller1234'),
            'role'     => 'teller',
        ]);

        User::create([
            'name'     => 'Teller 2',
            'username' => 'teller2',
            'password' => Hash::make('teller1234'),
            'role'     => 'teller',
        ]);

        User::create([
            'name'     => 'Runner 1',
            'username' => 'runner1',
            'password' => Hash::make('runner1234'),
            'role'     => 'runner',
        ]);

        User::create([
            'name'     => 'Admin',
            'username' => 'admin',
            'password' => Hash::make('admin1234'),
            'role'     => 'admin',
        ]);
    }
}
