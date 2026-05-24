<?php

namespace App\Observers;

use App\Models\Bet;
use App\Events\TellerCashStatusUpdated;

class BetObserver
{
    /**
     * Handle the Bet "created" event.
     * Broadcast to update teller cash status when a new bet is placed.
     */
    public function created(Bet $bet): void
    {
        \Log::info('🎲 BetObserver: New bet created for teller ' . $bet->teller_id . ' - Amount: ' . $bet->amount);

        // Broadcast the update - this will trigger financial overview to reload
        broadcast(new TellerCashStatusUpdated(
            $bet->teller_id,
            $bet->teller->name ?? 'Unknown',
            0,
            'bet_created',
            $bet->amount
        ))->toOthers();

        \Log::info('✅ Broadcast sent for new bet on teller ' . $bet->teller_id);
    }
}
