<?php

namespace App\Events\Event;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $buyOrder;

    public Order $sellOrder;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $buyOrder, Order $sellOrder)
    {
        $this->buyOrder = $buyOrder;
        $this->sellOrder = $sellOrder;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->buyOrder->user_id),
            new PrivateChannel('user.'.$this->sellOrder->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.matched';
    }
}
