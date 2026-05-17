<?php

namespace App\Http\Controllers\Api;

use App\Events\FightUpdated;
use App\Events\SideStatusUpdated;
use App\Events\WinnerDeclared;
use App\Events\TellerCashStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Fight;
use App\Models\Bet;
use App\Models\TellerCash;
use App\Services\AuditLogger;
use App\Services\PayoutCalculator;
use Illuminate\Http\Request;

class FightController extends Controller
{
    // GET /api/fight/current
    public function current()
    {
        $fight = Fight::whereIn('status', ['open', 'closed', 'pending'])
            ->whereNull('session_date') // only current session fights
            ->orderByRaw("FIELD(status, 'open', 'closed', 'pending')")
            ->latest()
            ->first();

        if (!$fight) {
            return response()->json(['message' => 'No active fight.'], 404);
        }

        return response()->json([
            'id'              => $fight->id,
            'fight_number'    => $fight->fight_number,
            'status'          => $fight->status,
            'meron_status'    => $fight->meron_status,
            'wala_status'     => $fight->wala_status,
            'winner'          => $fight->winner,
            'commission_rate' => $fight->commission_rate,
            'meron_total'     => (string) $fight->meronTotal(),
            'wala_total'      => (string) $fight->walaTotal(),
        ]);
    }

    // POST /api/fight
    public function store(Request $request)
    {
        $request->validate([
            'fight_number'    => ['required', 'string'],
            'commission_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ]);

        // check if there's already an active fight (only in current session)
        $activeFight = Fight::whereIn('status', ['pending', 'open', 'closed'])
            ->whereNull('session_date')
            ->first();

        if ($activeFight) {
            return response()->json([
                'message' => 'There is already an active fight. Please finish it before creating a new one.',
                'fight'   => [
                    'id'           => $activeFight->id,
                    'fight_number' => $activeFight->fight_number,
                    'status'       => $activeFight->status,
                ]
            ], 422);
        }

        $fight = Fight::create([
            'created_by'      => $request->user()->id,
            'fight_number'    => $request->fight_number,
            'status'          => 'pending',
            'meron_status'    => 'open',
            'wala_status'     => 'open',
            'commission_rate' => $request->commission_rate ?? 5.00,
            'session_date'    => null, // null means it's in the current active session
        ]);

        AuditLogger::log('created_fight', 'fight', $fight->id, [
            'fight_number' => $fight->fight_number,
        ]);

        broadcast(new FightUpdated($fight));

        return response()->json([
            'id'              => $fight->id,
            'fight_number'    => $fight->fight_number,
            'status'          => $fight->status,
            'meron_status'    => $fight->meron_status,
            'wala_status'     => $fight->wala_status,
            'winner'          => $fight->winner,
            'commission_rate' => $fight->commission_rate,
            'meron_total'     => (string) $fight->meronTotal(),
            'wala_total'      => (string) $fight->walaTotal(),
        ], 201);
    }

    // PUT /api/fight/{id}/status
    public function updateStatus(Request $request, Fight $fight)
    {
        $request->validate([
            'status' => ['required', 'in:pending,open,closed,cancelled'],
        ]);

        $fight->status = $request->status;

        // when opening — reset both sides to open
        if ($request->status === 'open') {
            $fight->meron_status = 'open';
            $fight->wala_status  = 'open';
        }

        // when closing all — close both sides
        if ($request->status === 'closed') {
            $fight->meron_status = 'closed';
            $fight->wala_status  = 'closed';
        }

        $fight->save();

        AuditLogger::log('updated_fight_status', 'fight', $fight->id, [
            'status' => $fight->status,
        ]);

        broadcast(new FightUpdated($fight));

        return response()->json(['message' => 'Fight status updated.']);
    }

    // PUT /api/fight/{id}/side-status
    public function updateSideStatus(Request $request, Fight $fight)
    {
        $request->validate([
            'side'   => ['required', 'in:meron,wala'],
            'status' => ['required', 'in:open,closed'],
        ]);

        if ($request->side === 'meron') {
            $fight->meron_status = $request->status;
        } else {
            $fight->wala_status = $request->status;
        }

        // if both sides are closed, close the fight status
        if ($fight->meron_status === 'closed' && $fight->wala_status === 'closed') {
            $fight->status = 'closed';
        }

        $fight->save();

        AuditLogger::log(
            'updated_side_status',
            'fight',
            $fight->id,
            [
                'side'   => $request->side,
                'status' => $request->status,
            ]
        );

        broadcast(new FightUpdated($fight));
        broadcast(new SideStatusUpdated($fight));

        return response()->json(['message' => ucfirst($request->side) . ' status updated.']);
    }

    // PUT /api/fight/{id}/all-side-status
    public function allSideStatus(Request $request, Fight $fight)
    {
        $request->validate([
            'status' => ['required', 'in:open,closed'],
        ]);

        $fight->meron_status = $request->status;
        $fight->wala_status = $request->status;

        // update fight status based on sides status
        if ($request->status === 'closed') {
            $fight->status = 'closed';
        } elseif ($request->status === 'open') {
            $fight->status = 'open';
        }

        $fight->save();

        AuditLogger::log(
            'updated_all_side_status',
            'fight',
            $fight->id,
            [
                'status' => $request->status,
            ]
        );

        broadcast(new FightUpdated($fight));
        broadcast(new SideStatusUpdated($fight));

        return response()->json(['message' => 'Both sides status updated to ' . $request->status . '.']);
    }

    // POST /api/fight/{id}/finalize
    public function finalizeBet(Fight $fight)
    {
        if ($fight->status !== 'open' && $fight->status !== 'closed') {
            return response()->json([
                'message' => 'Fight cannot be finalized.',
            ], 422);
        }

        $fight->status       = 'closed';
        $fight->meron_status = 'closed';
        $fight->wala_status  = 'closed';
        $fight->save();

        AuditLogger::log('finalized_bet', 'fight', $fight->id);

        broadcast(new FightUpdated($fight));

        return response()->json(['message' => 'Betting finalized.']);
    }

    // POST /api/fight/reset
    public function reset()
    {
        try {
            // Mark all existing fights with today's session date
            // This preserves ALL fights (including active ones) but isolates them from new session
            $today = now()->toDateString();
            $fightsUpdated = Fight::whereNull('session_date')->update(['session_date' => $today]);

            // Log the reset action
            $totalFights = Fight::count();
            AuditLogger::log('reset_fight_counter', 'system', null, [
                'message' => 'Fight counter reset to 1. New session started.',
                'session_date' => $today,
                'fights_in_previous_session' => $fightsUpdated,
                'total_fights_preserved' => $totalFights,
            ]);

            broadcast(new FightUpdated(new Fight()));

            return response()->json([
                'message' => 'Fight counter reset successfully to 1. New session started.',
                'session_date' => $today,
                'fights_moved_to_session' => $fightsUpdated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reset fight counter: ' . $e->getMessage(),
            ], 500);
        }
    }

    // POST /api/fight/{id}/winner
    public function declareWinner(Request $request, Fight $fight)
    {
        $request->validate([
            'winner' => ['required', 'in:meron,wala,draw,cancelled'],
        ]);

        if ($fight->status !== 'closed') {
            return response()->json([
                'message' => 'Fight must be closed before declaring a winner.',
            ], 422);
        }

        $fight->winner = $request->winner;
        $fight->status = 'done';
        $fight->save();

        // calculate payouts
        PayoutCalculator::calculate($fight);

        AuditLogger::log('declared_winner', 'fight', $fight->id, [
            'winner' => $fight->winner,
        ]);

        // Update TellerCash for all tellers with bets in this fight
        $tellerIds = Bet::where('fight_id', $fight->id)
            ->distinct('teller_id')
            ->pluck('teller_id')
            ->toArray();

        foreach ($tellerIds as $tellerId) {
            $tellerCash = TellerCash::updateTellerCash($tellerId);

            // Get teller info for broadcast
            $teller = \App\Models\User::find($tellerId);

            // Broadcast cash status update for each affected teller
            broadcast(new TellerCashStatusUpdated(
                $tellerId,
                $teller->name,
                $tellerCash->on_hand_cash,
                'winner_declared',
                0
            ));
        }

        broadcast(new WinnerDeclared($fight));

        return response()->json(['message' => 'Winner declared and payouts calculated.']);
    }

    // GET /api/fight/history
    public function history(Request $request)
    {
        $fights = Fight::whereNull('session_date') // only current session fights
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($f) => [
                'id'              => $f->id,
                'fight_number'    => $f->fight_number,
                'status'          => $f->status,
                'winner'          => $f->winner,
                'commission_rate' => $f->commission_rate,
                'meron_total'     => (string) $f->meronTotal(),
                'wala_total'      => (string) $f->walaTotal(),
            ]);

        return response()->json($fights);
    }
}
