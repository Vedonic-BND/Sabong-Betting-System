<?php

namespace App\Events;

use App\Models\Bet;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BetPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Bet $bet)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('fights'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bet.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'fight_id'  => $this->bet->fight_id,
            'side'      => $this->bet->side,
            'amount'    => $this->bet->amount,
            'teller'    => $this->bet->teller->name ?? '—',
        ];
    }
}
