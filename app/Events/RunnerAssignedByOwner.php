<?php

namespace App\Events;

use App\Models\CashRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RunnerAssignedByOwner implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public $cashRequest;

    public function __construct(CashRequest $cashRequest)
    {
        $this->cashRequest = $cashRequest->load(['runner', 'teller']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('cash-requests'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'runner.assigned-by-owner';
    }

    public function broadcastWith(): array
    {
        return [
            'id'              => $this->cashRequest->id,
            'runner_id'       => $this->cashRequest->runner_id,
            'runner_name'     => $this->cashRequest->runner?->name,
            'teller_id'       => $this->cashRequest->teller_id,
            'teller_name'     => $this->cashRequest->teller->name,
            'type'            => $this->cashRequest->type,
            'amount'          => $this->cashRequest->amount,
            'status'          => $this->cashRequest->status,
            'approved_at'     => $this->cashRequest->approved_at,
        ];
    }
}
