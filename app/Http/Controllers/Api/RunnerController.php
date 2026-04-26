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
                'amount' => ['required', 'numeric', 'min:0'],
                'type' => ['required', 'in:cash_in,cash_out'],
            ]);

            $runnerUser = auth()->user();
            $teller = User::find($validated['teller_id']);

            // Create cash request
            $cashRequest = CashRequest::create([
                'teller_id' => $validated['teller_id'],
                'runner_id' => $runnerUser->id,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'status' => 'completed',
                'reason' => 'Processed by runner',
            ]);

            // Calculate new on-hand cash for teller
            $totalCashIn = CashRequest::where('teller_id', $teller->id)
                ->where('type', 'cash_in')
                ->where('status', 'completed')
                ->sum('amount');

            $totalCashOut = CashRequest::where('teller_id', $teller->id)
                ->where('type', 'cash_out')
                ->where('status', 'completed')
                ->sum('amount');

            $onHandCash = $totalCashIn - $totalCashOut;

            // Broadcast event to runners
            event(new TellerCashStatusUpdated(
                $teller->id,
                $teller->name,
                $onHandCash,
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
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Transaction failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
