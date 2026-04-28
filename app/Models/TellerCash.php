<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Bet;
use App\Models\Payout;
use App\Models\CashRequest;

class TellerCash extends Model
{
    protected $table = 'teller_cash';

    protected $fillable = [
        'teller_id',
        'total_cash_in',
        'total_paid_out',
        'on_hand_cash',
        'last_updated',
    ];

    protected $casts = [
        'total_cash_in' => 'decimal:2',
        'total_paid_out' => 'decimal:2',
        'on_hand_cash' => 'decimal:2',
        'last_updated' => 'datetime',
    ];

    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    /**
     * Update teller cash totals
     * 
     * Logic:
     * - Teller RECEIVES bets from bettors (increases on_hand_cash)
     * - Teller PAYS OUT winnings (decreases on_hand_cash)
     * - Runner provides cash_in (increases on_hand_cash)
     * - Runner collects cash_out (decreases on_hand_cash)
     * 
     * on_hand_cash = (Bets Received + CashIn from Runner) - (Payouts Paid + CashOut to Runner)
     */
    public static function updateTellerCash(int $tellerId): self
    {
        try {
            // Calculate total cash IN (money teller receives):
            // 1. All bets placed by bettors with this teller
            $totalBetsReceived = Bet::where('teller_id', $tellerId)->sum('amount');

            // 2. Cash provided by runner (cash_in from runner transactions)
            $totalCashInFromRunner = \App\Models\CashRequest::where('teller_id', $tellerId)
                ->where('type', 'cash_in')
                ->where('status', 'completed')
                ->sum('amount');

            $totalCashIn = $totalBetsReceived + $totalCashInFromRunner;

            // Calculate total cash OUT (money teller pays out):
            // 1. Payouts from won bets
            $totalPayoutsPaid = Payout::join('bets', 'payouts.bet_id', '=', 'bets.id')
                ->where('bets.teller_id', $tellerId)
                ->where('payouts.status', 'paid')
                ->sum('payouts.net_payout');

            // 2. Cash collected by runner (cash_out from runner transactions)
            $totalCashOutFromRunner = \App\Models\CashRequest::where('teller_id', $tellerId)
                ->where('type', 'cash_out')
                ->where('status', 'completed')
                ->sum('amount');

            $totalCashOut = $totalPayoutsPaid + $totalCashOutFromRunner;

            // On-hand cash = Total In - Total Out
            $onHandCash = max(0, $totalCashIn - $totalCashOut);

            $tellerCash = self::updateOrCreate(
                ['teller_id' => $tellerId],
                [
                    'total_cash_in' => $totalCashIn,
                    'total_paid_out' => $totalCashOut,
                    'on_hand_cash' => $onHandCash,
                    'last_updated' => now(),
                ]
            );

            return $tellerCash;
        } catch (\Exception $e) {
            \Log::error('TellerCash::updateTellerCash Error for teller ' . $tellerId . ': ' . $e->getMessage());
            throw $e;
        }
    }
}

