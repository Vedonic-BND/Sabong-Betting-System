<?php

namespace App\Events;

use App\Models\CashRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public $cashRequest;

    public function __construct(CashRequest $cashRequest)
    {
        $this->cashRequest = $cashRequest->load(['teller', 'runner']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('cash-requests'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'request.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->cashRequest->id,
            'teller_name'  => $this->cashRequest->teller->name,
            'type'         => $this->cashRequest->type,
            'amount'       => $this->cashRequest->amount,
            'status'       => $this->cashRequest->status,
            'created_at'   => $this->cashRequest->created_at,
        ];
    }
}
