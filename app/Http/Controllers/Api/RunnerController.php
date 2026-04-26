<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TellerCash;
use App\Models\CashRequest;
use App\Events\TellerCashStatusUpdated;
use Illuminate\Http\Request;

class RunnerController extends Controller
{
    /**
     * Get cash status for all tellers
     * GET /api/runner/tellers
     */
    public function getTellersCashStatus(Request $request)
    {
        try {
            // Get all tellers with their cash status
            $tellers = User::where('role', 'teller')->get();

            $tellersCashStatus = $tellers->map(function ($teller) {
                // Get or create teller cash record and ensure it's up-to-date
                $tellerCash = TellerCash::updateTellerCash($teller->id);

                // Get last transaction date
                $lastTransaction = CashRequest::where('teller_id', $teller->id)
                    ->where('status', 'completed')
                    ->orderBy('updated_at', 'desc')
                    ->first();

                return [
                    'id' => $teller->id,
                    'name' => $teller->name,
                    'on_hand_cash' => (string)$tellerCash->on_hand_cash,
                    'last_transaction' => $lastTransaction ? $lastTransaction->updated_at->format('Y-m-d H:i:s') : null,
                ];
            });

            return response()->json($tellersCashStatus);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load tellers cash status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get runner transaction history
     * GET /api/runner/history
     */
    public function getHistory(Request $request)
    {
        try {
            // Get all completed cash requests that this runner has handled
            $runnerUser = auth()->user();

            $transactions = CashRequest::where('runner_id', $runnerUser->id)
                ->orWhere('approved_by', $runnerUser->id)
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($request) {
                    $date = $request->updated_at->format('Y-m-d');
                    $time = $request->updated_at->format('H:i:s');

                    return [
                        'id' => $request->id,
                        'runner_name' => auth()->user()->name ?? 'Unknown',
                        'teller_name' => $request->teller->name ?? 'Unknown',
                        'amount' => (string)$request->amount,
                        'type' => $request->type,
                        'status' => $request->status,
                        'date' => $date,
                        'time' => $time,
                    ];
                });

            return response()->json($transactions);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a transaction (runner approving/completing cash request)
     * POST /api/runner/transaction
     */
    public function createTransaction(Request $request)
    {
        try {
            $validated = $request->validate([
                'teller_id' => ['required', 'integer', 'exists:users,id'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'type' => ['required', 'in:cash_in,cash_out'],
            ]);

            $runnerUser = auth()->user();
            $teller = User::find($validated['teller_id']);

            // For collect (cash_out): validate that teller has enough on-hand cash
            if ($validated['type'] === 'cash_out') {
                $currentTellerCash = TellerCash::where('teller_id', $teller->id)->first();
                $tellerOnHandCash = $currentTellerCash ? $currentTellerCash->on_hand_cash : 0;

                if ($validated['amount'] > $tellerOnHandCash) {
                    return response()->json([
                        'message' => "Teller only has ₱" . number_format($tellerOnHandCash, 2) . " on-hand. Cannot collect ₱" . number_format($validated['amount'], 2) . ".",
                    ], 422);
                }
            }

            // Create cash request
            $cashRequest = CashRequest::create([
                'teller_id' => $validated['teller_id'],
                'runner_id' => $runnerUser->id,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'status' => 'completed',
                'reason' => 'Processed by runner',
            ]);

            // Ensure TellerCash record exists
            $tellerCash = TellerCash::firstOrCreate(
                ['teller_id' => $teller->id],
                [
                    'total_cash_in' => 0,
                    'total_paid_out' => 0,
                    'on_hand_cash' => 0,
                    'last_updated' => now(),
                ]
            );

            // Update teller's on-hand cash based on transaction type using raw update
            if ($validated['type'] === 'cash_out') {
                // COLLECT: Reduce teller's on-hand cash
                \DB::table('teller_cash')
                    ->where('teller_id', $teller->id)
                    ->update([
                        'on_hand_cash' => \DB::raw('GREATEST(0, on_hand_cash - ' . $validated['amount'] . ')'),
                        'last_updated' => now(),
                    ]);
            } else {
                // PROVIDE: Increase teller's on-hand cash
                \DB::table('teller_cash')
                    ->where('teller_id', $teller->id)
                    ->update([
                        'on_hand_cash' => \DB::raw('on_hand_cash + ' . $validated['amount']),
                        'last_updated' => now(),
                    ]);
            }

            // Refresh to get updated values
            $updatedTellerCash = TellerCash::where('teller_id', $teller->id)->first();

            // Broadcast event to runners with updated on-hand cash
            broadcast(new TellerCashStatusUpdated(
                $teller->id,
                $teller->name,
                $updatedTellerCash->on_hand_cash,
                $validated['type'],
                $validated['amount']
            ));

            $date = $cashRequest->created_at->format('Y-m-d');
            $time = $cashRequest->created_at->format('H:i:s');

            return response()->json([
                'id' => $cashRequest->id,
                'runner_name' => $runnerUser->name,
                'teller_name' => $cashRequest->teller->name,
                'amount' => (string)$cashRequest->amount,
                'type' => $cashRequest->type,
                'status' => $cashRequest->status,
                'date' => $date,
                'time' => $time,
                'teller_on_hand_cash' => (string)$updatedTellerCash->on_hand_cash,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Transaction failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
