<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\TellerCash;
use Illuminate\Database\Seeder;

class TellerCashSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Initialize TellerCash records for all tellers
        // $tellers = User::where('role', 'teller')->get();

        // foreach ($tellers as $teller) {
        //     TellerCash::updateTellerCash($teller->id);
        // }
    }
}

