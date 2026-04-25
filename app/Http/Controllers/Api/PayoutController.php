<?php

namespace App\Http\Controllers\Api;

use App\Events\TellerCashStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    // GET /api/payout/{reference}
    public function show(Request $request, string $reference)
    {
        $user = $request->user();

        $bet = Bet::with(['fight', 'payout', 'teller'])
            ->where('reference', $reference)
            ->where('teller_id', $user->id)
            ->first();

        if (!$bet) {
            return response()->json(['message' => 'Bet not found or unauthorized.'], 404);
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
            'teller'       => $bet->teller->name,
            'payout_date'  => $bet->payout->paid_at?->format('M d, Y'),
            'payout_time'  => $bet->payout->paid_at?->format('h:i A'),
        ]);
    }

    // POST /api/payout/{reference}
    public function confirm(Request $request, string $reference)
    {
        $user = $request->user();

        $bet = Bet::with('payout')
            ->where('reference', $reference)
            ->where('teller_id', $user->id)
            ->first();

        if (!$bet) {
            return response()->json(['message' => 'Bet not found or unauthorized.'], 404);
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

        // Calculate updated on-hand cash for this teller
        $tellerBets = Bet::where('teller_id', $user->id)
            ->with('payout')
            ->get();
        
        $totalCashIn = $tellerBets->sum('amount');
        $totalPaidOut = $tellerBets
            ->filter(fn($b) => $b->payout && $b->payout->status === 'paid')
            ->sum(fn($b) => $b->payout->net_payout);
        
        $onHandCash = $totalCashIn - $totalPaidOut;

        // Broadcast the update
        broadcast(new TellerCashStatusUpdated(
            $user->id,
            $user->name,
            $onHandCash,
            'payout',
            $bet->payout->net_payout
        ));

        return response()->json([
            'message'    => 'Payout confirmed.',
            'reference'  => $bet->reference,
            'net_payout' => $bet->payout->net_payout,
        ]);
    }
}
