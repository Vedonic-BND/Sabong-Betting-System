<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Bet;
use App\Models\Payout;

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
     */
    public static function updateTellerCash(int $tellerId): self
    {
        try {
            // Calculate total cash in from bets
            $totalCashIn = Bet::where('teller_id', $tellerId)->sum('amount');

            // Calculate total paid out using join through Bet
            $totalPaidOut = Payout::join('bets', 'payouts.bet_id', '=', 'bets.id')
                ->where('bets.teller_id', $tellerId)
                ->where('payouts.status', 'paid')
                ->sum('payouts.net_payout');

            $onHandCash = $totalCashIn - $totalPaidOut;

            $tellerCash = self::updateOrCreate(
                ['teller_id' => $tellerId],
                [
                    'total_cash_in' => $totalCashIn,
                    'total_paid_out' => $totalPaidOut,
                    'on_hand_cash' => max(0, $onHandCash),
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

