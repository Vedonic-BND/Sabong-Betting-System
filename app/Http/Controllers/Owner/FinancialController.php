<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Fight;
use App\Models\Payout;
use App\Models\User;
use App\Models\TellerCash;
use App\Models\CashRequest;

class FinancialController extends Controller
{
    public function overview()
    {
        $stats = [
            // Revenue metrics
            'total_earnings'     => Payout::where('status', 'paid')->sum('commission'),
            'pending_earnings'   => Payout::where('status', '!=', 'paid')->sum('commission'),

            // Bet metrics
            'total_bets'         => Bet::sum('amount'),
            'unclaimed_bets'     => Bet::leftJoin('payouts', 'bets.id', '=', 'payouts.bet_id')
                                        ->whereNull('payouts.id')
                                        ->orWhere('payouts.status', '!=', 'paid')
                                        ->sum('bets.amount'),

            // Payout metrics
            'total_payouts'      => Payout::sum('gross_payout'),
            'paid_payouts'       => Payout::where('status', 'paid')->sum('net_payout'),
            'pending_payouts'    => Payout::where('status', '!=', 'paid')->sum('gross_payout'),

            // Fight metrics
            'total_fights'       => Fight::count(),
            'fights_completed'   => Fight::where('status', 'done')->count(),

            // Bet count metrics
            'total_bet_count'    => Bet::count(),
            'unclaimed_bet_count' => Bet::leftJoin('payouts', 'bets.id', '=', 'payouts.bet_id')
                                         ->whereNull('payouts.id')
                                         ->orWhere('payouts.status', '!=', 'paid')
                                         ->count(),
        ];

        // Calculate summary metrics
        $summary = [
            'average_bet'        => $stats['total_bet_count'] > 0 ? $stats['total_bets'] / $stats['total_bet_count'] : 0,
            'average_payout'     => $stats['total_fights'] > 0 ? $stats['total_payouts'] / $stats['total_fights'] : 0,
            'commission_rate'    => $stats['total_payouts'] > 0 ? ($stats['total_earnings'] / $stats['total_payouts'] * 100) : 0,
        ];

        // Fetch all tellers with their cash status
        $tellers = User::where('role', 'teller')->get();
        $tellerCashStatus = [];

        foreach ($tellers as $teller) {
            $tellerCashData = TellerCash::where('teller_id', $teller->id)->first();

            if ($tellerCashData) {
                // Calculate only bets received (Bet In)
                $betInAmount = Bet::where('teller_id', $teller->id)->sum('amount');

                // Calculate only payouts paid (Payout)
                $payoutAmount = Payout::join('bets', 'payouts.bet_id', '=', 'bets.id')
                    ->where('bets.teller_id', $teller->id)
                    ->where('payouts.status', 'paid')
                    ->sum('payouts.net_payout');

                // Calculate provided and collected from runner transactions
                $cashProvided = CashRequest::where('teller_id', $teller->id)
                    ->where('type', 'cash_in')
                    ->where('status', 'completed')
                    ->sum('amount');

                $cashCollected = CashRequest::where('teller_id', $teller->id)
                    ->where('type', 'cash_out')
                    ->where('status', 'completed')
                    ->sum('amount');

                $tellerCashStatus[] = [
                    'teller_id' => $teller->id,
                    'teller_name' => $teller->name,
                    'total_cash_in' => $betInAmount,
                    'total_paid_out' => $payoutAmount,
                    'cash_provided' => $cashProvided,
                    'cash_collected' => $cashCollected,
                    'on_hand_cash' => $tellerCashData->on_hand_cash,
                    'last_updated' => $tellerCashData->last_updated,
                ];
            }
        }

        return view('owner.financial-overview', compact('stats', 'summary', 'tellerCashStatus'));
    }
}
