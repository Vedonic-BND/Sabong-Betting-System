<?php

namespace App\Http\Controllers\Api;

use App\Events\BetPlaced;
use App\Events\BetDeleted;
use App\Events\TellerCashStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Fight;
use App\Models\TellerCash;
use App\Services\AuditLogger;
use App\Services\QrGenerator;
use Illuminate\Broadcasting\Channel;
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
                    'fight_number' => $bet->fight?->fight_number ?? null,
                    'side'         => strtoupper($bet->side),
                    'amount'       => number_format($bet->amount, 2),
                    'reference'    => $bet->reference,
                    'date'         => $bet->created_at->format('M d, Y'),
                    'time'         => $bet->created_at->format('h:i A'),
                ],
                'winner'       => $bet->fight?->winner,
                'won'          => $bet->fight ? $bet->side === $bet->fight->winner : null,
                'status'       => $bet->payout?->status ?? 'pending',
                'gross_payout' => $bet->payout?->gross_payout ? number_format($bet->payout->gross_payout, 2) : null,
                'net_payout'   => $bet->payout?->net_payout ? number_format($bet->payout->net_payout, 2) : null,
                'payout_date'  => $bet->payout && $bet->payout->paid_at ? $bet->payout->paid_at->format('M d, Y') : null,
                'payout_time'  => $bet->payout && $bet->payout->paid_at ? $bet->payout->paid_at->format('h:i A') : null,
                'bet' => [
                    'id'           => $bet->id,
                    'reference'    => $bet->reference,
                    'fight_number' => $bet->fight?->fight_number ?? null,
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

        // Get open fight only from current session (session_date = null)
        $fight = Fight::where('status', 'open')
            ->whereNull('session_date')
            ->latest()
            ->first();

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

    // GET /api/admin/bet/history — Get all bets placed by authenticated admin
    public function adminHistory(Request $request)
    {
        $user = $request->user();

        // Get bets for authenticated admin only with payout relationship
        $bets = Bet::where('teller_id', $user->id)
            ->with(['fight', 'payout'])
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedBets = $bets->map(function ($bet) {
            return [
                'reference'    => $bet->reference,
                'message'      => 'Bet retrieved successfully.',
                'qr'           => null,
                'barcode'      => null,
                'receipt' => [
                    'fight_number' => $bet->fight?->fight_number ?? null,
                    'side'         => strtoupper($bet->side),
                    'amount'       => number_format($bet->amount, 2),
                    'reference'    => $bet->reference,
                    'date'         => $bet->created_at->format('M d, Y'),
                    'time'         => $bet->created_at->format('h:i A'),
                ],
                'bet' => [
                    'id'           => $bet->id,
                    'reference'    => $bet->reference,
                    'fight_number' => $bet->fight?->fight_number ?? null,
                    'side'         => $bet->side,
                    'amount'       => (string)$bet->amount,
                    'created_at'   => $bet->created_at,
                ],
                'winner'       => $bet->fight?->winner,
                'won'          => $bet->fight ? $bet->side === $bet->fight->winner : null,
                'status'       => $bet->payout?->status ?? 'pending',
                'net_payout'   => $bet->payout?->net_payout ? number_format($bet->payout->net_payout, 2) : null,
            ];
        });

        return response()->json([
            'data' => $formattedBets,
        ], 200);
    }

    // DELETE /api/admin/bet/{id} — Delete a bet placed by admin
    public function adminDestroyBet(Request $request, $betId)
    {
        \Log::info("🗑️ [DELETE ENDPOINT] Called for Bet ID: $betId");

        $user = $request->user();

        $bet = Bet::where('id', $betId)
            ->where('teller_id', $user->id)
            ->first();

        if (!$bet) {
            \Log::warning("🗑️ [DELETE ENDPOINT] Bet $betId not found or unauthorized");
            return response()->json([
                'message' => 'Bet not found or unauthorized.',
            ], 404);
        }

        // Log before deletion
        $fightId = $bet->fight_id;
        $betSide = $bet->side;
        $betAmount = $bet->amount;
        $tellerName = $bet->teller->name ?? '—';
        \Log::info("🎯 [BEFORE DELETE] Bet ID: $betId, Fight ID: $fightId, Side: {$bet->side}, Amount: {$bet->amount}");

        // Get fight before deletion to show totals
        $fightBefore = Fight::find($fightId);
        \Log::info("📊 [BEFORE DELETE] Fight $fightId totals - Meron: {$fightBefore->meronTotal()}, Wala: {$fightBefore->walaTotal()}");

        // Log the deletion
        AuditLogger::log('deleted_bet', 'bet', $bet->id, [
            'reference' => $bet->reference,
            'side'      => $bet->side,
            'amount'    => $bet->amount,
        ]);

        // Actually delete the bet FIRST
        $deleteResult = $bet->delete();
        \Log::info("🗑️ [DELETE RESULT] Bet deletion returned: " . ($deleteResult ? 'TRUE' : 'FALSE'));

        // Get fight AFTER deletion to get correct totals
        $fightAfter = Fight::find($fightId);
        \Log::info("📊 [AFTER DELETE] Fight $fightId totals - Meron: {$fightAfter->meronTotal()}, Wala: {$fightAfter->walaTotal()}");

        // Prepare broadcast data BEFORE creating event
        $broadcastData = [
            'fight_id'      => $fightId,
            'fight_number'  => $fightAfter->fight_number ?? null,
            'status'        => $fightAfter->status ?? 'pending',
            'meron_status'  => $fightAfter->meron_status ?? 'open',
            'wala_status'   => $fightAfter->wala_status ?? 'open',
            'side'          => $betSide,
            'amount'        => $betAmount,
            'teller'        => $tellerName,
            'meron_total'   => (float)$fightAfter->meronTotal(),
            'wala_total'    => (float)$fightAfter->walaTotal(),
        ];

        \Log::info("📡 [BROADCAST DATA] Prepared: " . json_encode($broadcastData));

        // Broadcast with shouldQueue=false means it goes directly to Reverb, not the queue
        \Log::info("🎯 [BROADCAST] Dispatching BetDeleted event (shouldQueue=false, so synchronous)");
        broadcast(new BetDeleted($bet, $broadcastData))->toOthers();

        // Verify bet was actually deleted
        $betStillExists = Bet::find($betId);
        \Log::info("🔍 [VERIFICATION] Bet $betId still exists: " . ($betStillExists ? 'YES (ERROR!)' : 'NO (Correct)'));

        return response()->json([
            'message' => 'Bet deleted successfully.',
        ], 200);
    }
}

