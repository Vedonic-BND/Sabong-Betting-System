<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TellerCashStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tellerId;
    public $tellerName;
    public $onHandCash;
    public $type;
    public $amount;
    public $timestamp;

    public function __construct($tellerId, $tellerName, $onHandCash, $type, $amount)
    {
        $this->tellerId = $tellerId;
        $this->tellerName = $tellerName;
        $this->onHandCash = $onHandCash;
        $this->type = $type;
        $this->amount = $amount;
        $this->timestamp = now()->format('Y-m-d H:i:s');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('cash-status'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'teller.cash-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'teller_id' => $this->tellerId,
            'teller_name' => $this->tellerName,
            'on_hand_cash' => (string)$this->onHandCash,
            'type' => $this->type,
            'amount' => (string)$this->amount,
            'timestamp' => $this->timestamp,
        ];
    }
}
