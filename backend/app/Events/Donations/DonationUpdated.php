<?php

namespace App\Events\Donations;

use App\Http\Resources\Donations\DonationLogResource;
use App\Models\Donations\DonationLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class DonationUpdated implements ShouldBroadcast, ShouldQueue
{
    use SerializesModels;

    public string $queue = 'notifications';

    public DonationLog $data;

    public function __construct(DonationLog $donationLog)
    {
        $this->data = $donationLog;
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
        return ['resource' => new DonationLogResource($this->data)];
    }

    public function broadcastAs(): string
    {
        return 'donation.updated';
    }
}
