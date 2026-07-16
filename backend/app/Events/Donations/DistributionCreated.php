<?php

namespace App\Events\Donations;

use App\Http\Resources\Donations\DonationDistributionResource;
use App\Models\Donations\DonationDistribution;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class DistributionCreated implements ShouldBroadcast, ShouldQueue
{
    use SerializesModels;

    public string $queue = 'notifications';

    public DonationDistribution $data;

    public function __construct(DonationDistribution $donationDistribution)
    {
        $this->data = $donationDistribution;
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
        return ['resource' => new DonationDistributionResource($this->data)];
    }

    public function broadcastAs(): string
    {
        return 'distribution.created';
    }
}
