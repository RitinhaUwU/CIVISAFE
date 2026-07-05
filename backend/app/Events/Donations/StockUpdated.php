<?php

namespace App\Events\Donations;

use App\Models\Donations\DonationStock;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;

class StockUpdated implements ShouldBroadcast, ShouldQueue
{
    public string $queue = 'notifications';

    public int $goodsTypeId;
    public int $stock;

    public function __construct(DonationStock $donationStock)
    {
        $this->goodsTypeId = $donationStock->donation_goods_type_id;
        $this->stock = $donationStock->stock;
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
            'id' => $this->goodsTypeId,
            'stock' => $this->stock,
            'timestamp' => Carbon::now()->timestamp
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.updated';
    }
}
