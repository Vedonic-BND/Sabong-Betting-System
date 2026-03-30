<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Fight;

class FightController extends Controller
{
    public function index()
    {
        $fights = Fight::with(['creator', 'bets'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('owner.fights.index', compact('fights'));
    }

    public function show(Fight $fight)
    {
        $fight->load(['creator', 'bets.teller', 'bets.payout']);

        $summary = [
            'total_bets'   => $fight->bets->count(),
            'meron_total'  => $fight->meronTotal(),
            'wala_total'   => $fight->walaTotal(),
            'total_amount' => $fight->totalBets(),
        ];

        return view('owner.fights.show', compact('fight', 'summary'));
    }
}
