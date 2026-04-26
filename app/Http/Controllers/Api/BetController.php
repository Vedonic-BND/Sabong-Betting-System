<?php

namespace App\Http\Controllers\Api;

use App\Events\BetPlaced;
use App\Events\TellerCashStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Fight;
use App\Models\TellerCash;
use App\Services\AuditLogger;
use App\Services\QrGenerator;
use Illuminate\Http\Request;

class BetController extends Controller
{
    // GET /api/bet/history — Get all bets for authenticated teller
    public function index(Request $request)
    {
        $user = $request->user();

        // Get bets for authenticated teller only with payout relationship
        $bets = Bet::where('teller_id', $user->id)
            ->with(['fight', 'payout'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedBets = $bets->map(function ($bet) {
            return [
                'reference'    => $bet->reference,
                'message'      => 'Bet placed successfully.',
                'qr'           => null,
                'barcode'      => null,
                'receipt' => [
                    'fight_number' => $bet->fight->fight_number ?? null,
                    'side'         => strtoupper($bet->side),
                    'amount'       => number_format($bet->amount, 2),
                    'reference'    => $bet->reference,
                    'date'         => $bet->created_at->format('M d, Y'),
                    'time'         => $bet->created_at->format('h:i A'),
                ],
                'winner'       => $bet->fight->winner,
                'won'          => $bet->side === $bet->fight->winner,
                'status'       => $bet->payout?->status ?? 'pending',
                'gross_payout' => $bet->payout?->gross_payout ? number_format($bet->payout->gross_payout, 2) : null,
                'net_payout'   => $bet->payout?->net_payout ? number_format($bet->payout->net_payout, 2) : null,
                'payout_date'  => $bet->payout && $bet->payout->paid_at ? $bet->payout->paid_at->format('M d, Y') : null,
                'payout_time'  => $bet->payout && $bet->payout->paid_at ? $bet->payout->paid_at->format('h:i A') : null,
                'bet' => [
                    'id'           => $bet->id,
                    'reference'    => $bet->reference,
                    'fight_number' => $bet->fight->fight_number ?? null,
                    'side'         => $bet->side,
                    'amount'       => $bet->amount,
                    'created_at'   => $bet->created_at,
                ],
            ];
        });

        return response()->json([
            'data' => $formattedBets,
        ], 200);
    }

    // GET /api/bet/{reference} — Get specific bet by reference
    public function show(Request $request, $reference)
    {
        $user = $request->user();

        $bet = Bet::where('reference', $reference)
            ->where('teller_id', $user->id)
            ->with(['fight', 'payout'])
            ->first();

        if (!$bet) {
            return response()->json([
                'message' => 'Bet not found or unauthorized.',
            ], 404);
        }

        return response()->json([
            'reference'    => $bet->reference,
            'message'      => 'Bet retrieved successfully.',
            'qr'           => null,
            'barcode'      => null,
            'receipt' => [
                'fight_number' => $bet->fight->fight_number ?? null,
                'side'         => strtoupper($bet->side),
                'amount'       => number_format($bet->amount, 2),
                'reference'    => $bet->reference,
                'date'         => $bet->created_at->format('M d, Y'),
                'time'         => $bet->created_at->format('h:i A'),
            ],
            'bet' => [
                'id'           => $bet->id,
                'reference'    => $bet->reference,
                'fight_number' => $bet->fight->fight_number ?? null,
                'side'         => $bet->side,
                'amount'       => $bet->amount,
                'created_at'   => $bet->created_at,
            ],
        ], 200);
    }

    // POST /api/bet
    public function store(Request $request)
    {
        $request->validate([
            'side'   => ['required', 'in:meron,wala'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $fight = Fight::where('status', 'open')->latest()->first();

        if (!$fight) {
            return response()->json([
                'message' => 'No open fight available.',
            ], 422);
        }

        // check side status
        $sideStatus = $request->side === 'meron'
            ? $fight->meron_status
            : $fight->wala_status;

        if ($sideStatus === 'closed') {
            return response()->json([
                'message' => ucfirst($request->side) . ' betting is closed.',
            ], 422);
        }

        $bet = Bet::create([
            'fight_id'  => $fight->id,
            'teller_id' => $request->user()->id,
            'side'      => $request->side,
            'amount'    => $request->amount,
        ]);

        AuditLogger::log('placed_bet', 'bet', $bet->id, [
            'side'   => $bet->side,
            'amount' => $bet->amount,
        ]);

        broadcast(new BetPlaced($bet));

        // Update and broadcast teller's on-hand cash
        $tellerCash = TellerCash::updateTellerCash($request->user()->id);

        broadcast(new TellerCashStatusUpdated(
            $request->user()->id,
            $request->user()->name,
            $tellerCash->on_hand_cash,
            'bet',
            $request->amount
        ));

        return response()->json([
            'message'   => 'Bet placed successfully.',
            'reference' => $bet->reference,
            'qr'        => QrGenerator::generateQr($bet->reference),
            'barcode'   => QrGenerator::generateBarcode($bet->reference),
            'receipt'   => [
                'fight_number' => $fight->fight_number,
                'side'         => strtoupper($bet->side),
                'amount'       => number_format($bet->amount, 2),
                'reference'    => $bet->reference,
                'teller'       => $request->user()->name,
                'date'         => now()->format('M d, Y'),
                'time'         => now()->format('h:i A'),
            ],
            'bet' => [
                'id'           => $bet->id,
                'reference'    => $bet->reference,
                'fight_number' => $fight->fight_number,
                'side'         => $bet->side,
                'amount'       => $bet->amount,
                'created_at'   => $bet->created_at,
            ],
        ], 201);
    }
}
