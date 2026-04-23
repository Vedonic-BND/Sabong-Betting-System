<?php

namespace App\Services;

use App\Models\Fight;

class PayoutCalculator
{
    /**
     * Calculate payout for each bet in a fight after winner is declared.
     *
     * Formula:
     *   Net Pool = (Meron + Wala) × 0.95  ← 5% commission (95% payout)
     *   Winner multiplier = (Net Pool / Winning Side Total) × 100
     *   Winner payout = (bet amount / winning side total) × Net Pool
     */
    public static function calculate(Fight $fight): void
    {
        $meronTotal = $fight->meronTotal();
        $walaTotal  = $fight->walaTotal();
        $totalPool  = $meronTotal + $walaTotal;

        // If no bets, nothing to calculate
        if ($totalPool <= 0) {
            return;
        }

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
                    'winning_side_multiplier' => 100.00,
                    'status'       => 'pending',
                ]);
            }
            return;
        }

        $winningSideTotal = $winner === 'meron' ? $meronTotal : $walaTotal;

        // Check if winning side has any bets
        if ($winningSideTotal <= 0) {
            return; // No bets on winning side, nothing to calculate
        }

        // Calculate the multiplier for the winning side (same as displayed on screen)
        $winningMultiplier = ($netPool / $winningSideTotal) * 100;

        foreach ($fight->bets as $bet) {
            if ($bet->side === $winner) {
                // winner gets proportional share of net pool
                $gross      = ($bet->amount / $winningSideTotal) * $netPool;
                $betCommission = ($bet->amount / $winningSideTotal) * $commission;
                $net        = $gross;
            } else {
                // loser gets nothing
                $gross         = 0;
                $losingTotal = $totalPool - $winningSideTotal;
                $betCommission = $losingTotal > 0 ? ($bet->amount / $losingTotal) * $commission : 0;
                $net           = 0;
            }

            $bet->payout()->create([
                'gross_payout' => round($gross, 2),
                'commission'   => round($betCommission, 2),
                'net_payout'   => round($net, 2),
                'winning_side_multiplier' => round($winningMultiplier, 2),
                'status'       => 'pending',
            ]);
        }
    }
}
