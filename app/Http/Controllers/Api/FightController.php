<?php

namespace App\Http\Controllers\Api;

use App\Events\FightUpdated;
use App\Events\WinnerDeclared;
use App\Http\Controllers\Controller;
use App\Models\Fight;
use App\Services\AuditLogger;
use App\Services\PayoutCalculator;
use Illuminate\Http\Request;

class FightController extends Controller
{
    // GET /api/fight/current
    public function current()
    {
        $fight = Fight::whereIn('status', ['open', 'closed', 'pending'])
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

        $fight = Fight::create([
            'created_by'      => $request->user()->id,
            'fight_number'    => $request->fight_number,
            'status'          => 'pending',
            'commission_rate' => $request->commission_rate ?? 5.00,
        ]);

        AuditLogger::log('created_fight', 'fight', $fight->id, [
            'fight_number' => $fight->fight_number,
        ]);

        broadcast(new FightUpdated($fight));

        return response()->json($fight, 201);
    }

    // PUT /api/fight/{id}/status
    public function updateStatus(Request $request, Fight $fight)
    {
        $request->validate([
            'status' => ['required', 'in:pending,open,closed,cancelled'],
        ]);

        $fight->status = $request->status;
        $fight->save();

        AuditLogger::log('updated_fight_status', 'fight', $fight->id, [
            'status' => $fight->status,
        ]);

        broadcast(new FightUpdated($fight));

        return response()->json(['message' => 'Fight status updated.']);
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

        broadcast(new WinnerDeclared($fight));

        return response()->json(['message' => 'Winner declared and payouts calculated.']);
    }

    // GET /api/fight/history
    public function history(Request $request)
    {
        $fights = Fight::orderBy('created_at', 'desc')
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
