<?php

namespace App\Events\Donations;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;

class StockLockUpdate implements ShouldBroadcast, ShouldQueue
{
    public string $queue = 'notifications';

    public bool $unlocked;

    public function __construct(bool $unlocked)
    {
        $this->unlocked = $unlocked;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('DonationStocks'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'isUnlocked' => $this->unlocked
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.lock_status';
    }
}
