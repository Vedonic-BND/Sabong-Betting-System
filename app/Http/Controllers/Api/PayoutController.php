<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    // GET /api/payout/{reference}
    public function show(string $reference)
    {
        $bet = Bet::with(['fight', 'payout', 'teller'])
            ->where('reference', $reference)
            ->first();

        if (!$bet) {
            return response()->json(['message' => 'Bet not found.'], 404);
        }

        if (!$bet->payout) {
            return response()->json(['message' => 'Payout not yet calculated.'], 422);
        }

        return response()->json([
            'reference'    => $bet->reference,
            'fight'        => $bet->fight->fight_number,
            'side'         => $bet->side,
            'bet_amount'   => $bet->amount,
            'winner'       => $bet->fight->winner,
            'won'          => $bet->side === $bet->fight->winner,
            'gross_payout' => $bet->payout->gross_payout,
            'commission'   => $bet->payout->commission,
            'net_payout'   => $bet->payout->net_payout,
            'status'       => $bet->payout->status,
        ]);
    }

    // POST /api/payout/{reference}
    public function confirm(string $reference)
    {
        $bet = Bet::with('payout')
            ->where('reference', $reference)
            ->first();

        if (!$bet) {
            return response()->json(['message' => 'Bet not found.'], 404);
        }

        if (!$bet->payout) {
            return response()->json(['message' => 'Payout not yet calculated.'], 422);
        }

        if ($bet->payout->status === 'paid') {
            return response()->json(['message' => 'Already paid.'], 422);
        }

        if ($bet->payout->net_payout <= 0) {
            return response()->json(['message' => 'This bet lost. No payout.'], 422);
        }

        $bet->payout->status = 'paid';
        $bet->payout->save();

        AuditLogger::log('paid_payout', 'bet', $bet->id, [
            'reference'  => $bet->reference,
            'net_payout' => $bet->payout->net_payout,
        ]);

        return response()->json([
            'message'    => 'Payout confirmed.',
            'reference'  => $bet->reference,
            'net_payout' => $bet->payout->net_payout,
        ]);
    }
}
