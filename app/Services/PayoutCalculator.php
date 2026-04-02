<?php

namespace App\Services;

use App\Models\Fight;

class PayoutCalculator
{
    /**
     * Calculate payout for each bet in a fight after winner is declared.
     *
     * Formula:
     *   Net Pool = (Meron + Wala) × 0.90  ← 10% commission
     *   Winner payout = (bet amount / winning side total) × Net Pool
     */
    public static function calculate(Fight $fight): void
    {
        $meronTotal = $fight->meronTotal();
        $walaTotal  = $fight->walaTotal();
        $totalPool  = $meronTotal + $walaTotal;
        $netPool    = $totalPool * 0.95;
        $commission = $totalPool * 0.05;
        $winner     = $fight->winner;

        // draw or cancelled — full refund, no commission
        if (!in_array($winner, ['meron', 'wala'])) {
            foreach ($fight->bets as $bet) {
                $bet->payout()->create([
                    'gross_payout' => $bet->amount,
                    'commission'   => 0,
                    'net_payout'   => $bet->amount,
                    'status'       => 'pending',
                ]);
            }
            return;
        }

        $winningSideTotal = $winner === 'meron' ? $meronTotal : $walaTotal;

        foreach ($fight->bets as $bet) {
            if ($bet->side === $winner) {
                // winner gets proportional share of net pool
                $gross      = ($bet->amount / $winningSideTotal) * $netPool;
                $betCommission = ($bet->amount / $winningSideTotal) * $commission;
                $net        = $gross;
            } else {
                // loser gets nothing
                $gross         = 0;
                $betCommission = ($bet->amount / ($totalPool - $winningSideTotal)) * $commission;
                $net           = 0;
            }

            $bet->payout()->create([
                'gross_payout' => round($gross, 2),
                'commission'   => round($betCommission, 2),
                'net_payout'   => round($net, 2),
                'status'       => 'pending',
            ]);
        }
    }
}
