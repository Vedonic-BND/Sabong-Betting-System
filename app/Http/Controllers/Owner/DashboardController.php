<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Fight;
use App\Models\Payout;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_admins'       => User::where('role', 'admin')->count(),
            'total_tellers'      => User::where('role', 'teller')->count(),
            'total_fights'       => Fight::count(),
            'fights_completed'   => Fight::where('status', 'done')->count(),
            'total_bets'         => Bet::sum('amount'),
            'total_earnings'     => Payout::where('status', 'paid')->sum('commission'),
            'unclaimed_bets'     => Bet::leftJoin('payouts', 'bets.id', '=', 'payouts.bet_id')
                                        ->whereNull('payouts.id')
                                        ->orWhere('payouts.status', '!=', 'paid')
                                        ->sum('bets.amount'),
        ];

        return view('owner.dashboard', compact('stats'));
    }
}
