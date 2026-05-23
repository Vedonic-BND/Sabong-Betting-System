<?php

namespace App\Events;

use App\Models\Bet;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class BetDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    private $customBroadcastData = null;

    public function __construct(public ?Bet $bet, $broadcastData = null)
    {
        $this->customBroadcastData = $broadcastData;
    }

    /**
     * Determine if the event should be dispatched through a queue.
     * Return false to process synchronously.
     */
    public function shouldQueue(): bool
    {
        return false;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('fights'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bet.deleted';
    }

    /**
     * Get the data that should be sent with the broadcasted event.
     */
    public function broadcastWith(): array
    {
        // If custom broadcast data was provided, use it
        if ($this->customBroadcastData !== null) {
            \Log::info("📡 [BROADCAST] BetDeleted using custom data: " . json_encode($this->customBroadcastData));
            return $this->customBroadcastData;
        }

        // Fallback to calculating from the bet (if it still exists)
        $fight = $this->bet->fight;

        if (!$fight) {
            \Log::error("❌ [BROADCAST ERROR] BetDeleted - Fight not found for bet {$this->bet->id}");
            return [];
        }

        $broadcastData = [
            'fight_id'      => $this->bet->fight_id,
            'fight_number'  => $fight->fight_number ?? null,
            'status'        => $fight->status ?? 'pending',
            'meron_status'  => $fight->meron_status ?? 'open',
            'wala_status'   => $fight->wala_status ?? 'open',
            'side'          => $this->bet->side,
            'amount'        => $this->bet->amount,
            'teller'        => $this->bet->teller->name ?? '—',
            'meron_total'   => (float)$fight->meronTotal(),
            'wala_total'    => (float)$fight->walaTotal(),
        ];

        \Log::info("📡 [BROADCAST] BetDeleted event - Fight #{$fight->fight_number}, meron={$broadcastData['meron_total']}, wala={$broadcastData['wala_total']}");

        return $broadcastData;
    }
}

