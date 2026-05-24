<?php

namespace App\Observers;

use App\Models\TellerCash;
use App\Events\TellerCashStatusUpdated;

class TellerCashObserver
{
    /**
     * Handle the TellerCash "created" event.
     */
    public function created(TellerCash $tellerCash): void
    {
        \Log::debug('🔔 TellerCashObserver: TellerCash created for teller ' . $tellerCash->teller_id);

        broadcast(new TellerCashStatusUpdated(
            $tellerCash->teller_id,
            $tellerCash->teller->name ?? 'Unknown',
            $tellerCash->on_hand_cash,
            'created',
            0
        ))->toOthers();
    }

    /**
     * Handle the TellerCash "updated" event.
     * Only broadcast if actual values changed (not just timestamps).
     */
    public function updated(TellerCash $tellerCash): void
    {
        $changes = $tellerCash->getChanges();

        \Log::info('🔔 TellerCashObserver.updated() called for teller ' . $tellerCash->teller_id);
        \Log::info('Changes: ' . json_encode($changes));

        // If only timestamps changed, don't broadcast
        $dataChanged = false;
        foreach ($changes as $field => $value) {
            if (!in_array($field, ['last_updated', 'updated_at'])) {
                $dataChanged = true;
                break;
            }
        }

        if (!$dataChanged) {
            \Log::info('⏭️ TellerCashObserver: Skipping - only timestamps changed for teller ' . $tellerCash->teller_id);
            return;
        }

        // Actual data changed, broadcast the event
        \Log::info('✅ Broadcasting TellerCashStatusUpdated event for teller ' . $tellerCash->teller_id);

        broadcast(new TellerCashStatusUpdated(
            $tellerCash->teller_id,
            $tellerCash->teller->name ?? 'Unknown',
            $tellerCash->on_hand_cash,
            'updated',
            0
        ))->toOthers();
    }
}
