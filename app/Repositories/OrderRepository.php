<?php

namespace App\Repositories;

use App\Models\OrderBook;

class OrderRepository
{
    /**
     * @param $data
     * @return mixed
     */
    public function create($data)
    {
        return OrderBook::create($data);
    }

    /**
     * @param $orderId
     * @return mixed
     */
    public function getByOrderId($orderId)
    {
        return OrderBook::where('order_id', $orderId)->first();
    }

    /**
     * @param $orderId
     * @return bool|null
     */
    public function delete($orderId)
    {
        $order = $this->getByOrderId($orderId);
        if (!$order) {
            return false;
        }

        $order->delete();
        return true;
    }

    public function getLastUpdateId()
    {
        return OrderBook::latest()->first()->created_at->now()->timestamp;
    }

    /**
     * @param $limit
     * @return mixed
     */
    public function getRandomData($limit)
    {
        return OrderBook::inRandomOrder()->limit(200)->get();
    }
}
