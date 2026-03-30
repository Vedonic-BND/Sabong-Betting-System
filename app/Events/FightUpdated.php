<?php

namespace App\Events;

use App\Models\Fight;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FightUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Fight $fight)
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
        return 'fight.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->fight->id,
            'fight_number'=> $this->fight->fight_number,
            'status'      => $this->fight->status,
            'winner'      => $this->fight->winner,
            'meron_total' => $this->fight->meronTotal(),
            'wala_total'  => $this->fight->walaTotal(),
        ];
    }
}
