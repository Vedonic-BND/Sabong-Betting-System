<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Fight;
use Illuminate\Http\Request;

class FightController extends Controller
{
    public function index(Request $request)
    {
        $fights = Fight::with(['creator', 'bets'])
            ->when($request->fight_number, fn($q) =>
                $q->where('fight_number', 'like', '%' . $request->fight_number . '%')
            )
            ->when($request->winner, fn($q) =>
                $q->where('winner', 'like', '%' . strtolower($request->winner) . '%')
            )
            ->when($request->date, fn($q) =>
                $q->whereDate('created_at', $request->date)
            )
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
