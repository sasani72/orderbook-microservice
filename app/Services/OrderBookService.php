<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\Repositories\OrderRepository;

class OrderBookService
{
    private $maxOrderBookDepth = 5000 * 2; // because of score and member pairs
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function updateRedisOrderBook($order)
    {
        $symbol = $order['symbol'];
        $price = $order['price'];
        $quantity = $order['quantity'];

        if ($order['side'] === 'buy') {
            $this->updateRedisBuyOrderBook($symbol, $price, $quantity);
        } else {
            $this->updateRedisSellOrderBook($symbol, $price, $quantity);
        }
    }

    private function updateRedisBuyOrderBook($symbol, $price, $quantity)
    {
        $key = "buy_orders_$symbol";
        // Check if the price exists in the sorted set
        $existingPriceQuantity = Redis::zscore($key, $price);

        if ($existingPriceQuantity !== false) {
            // Price exists, update the quantity by adding the new quantity
            $newQuantity = $existingPriceQuantity + $quantity;
            Redis::zadd($key, [$price => $newQuantity]);
        } else {
            // Price doesn't exist, add a new record
            Redis::zadd($key, [$price => $quantity]);
        }

        $this->limitRedisSetSizeBuy($key);
    }

    private function updateRedisSellOrderBook($symbol, $price, $quantity)
    {
        $key = "sell_orders_$symbol";
        // Check if the price already exists in the sorted set
        $existingPriceQuantity = Redis::zscore($key, $price);

        if ($existingPriceQuantity !== false) {
            // Price exists, update the quantity by adding the new quantity
            $newQuantity = $existingPriceQuantity + $quantity;
            Redis::zadd($key, [$price => $newQuantity]);
        } else {
            // Price doesn't exist, add a new record
            Redis::zadd($key, [$price => $quantity]);
        }

        $this->limitRedisSetSizeSell($key);
    }

    private function limitRedisSetSizeBuy($key)
    {
        $count = Redis::zcard($key);
        if ($count > $this->maxOrderBookDepth) {
            // Calculate the number of records to remove
            $recordsToRemove = $count - $this->maxOrderBookDepth;

            // Retrieve and remove the records with the lowest scores (prices)
            $lowestScores = Redis::zrevrange($key, 0, $recordsToRemove - 1);

            // Remove the records with the lowest scores
            Redis::zrem($key, ...$lowestScores);
        }
    }

    private function limitRedisSetSizeSell($key)
    {
        $count = Redis::zcard($key);
        if ($count > $this->maxOrderBookDepth) {
            // Calculate the number of records to remove
            $recordsToRemove = $count - $this->maxOrderBookDepth;

            // Retrieve and remove the records with the highest scores (prices)
            $highestScores = Redis::zrange($key, 0, $recordsToRemove - 1);

            // Remove the records with the highest scores
            Redis::zrem($key, ...$highestScores);
        }
    }

    public function getOrderBook($symbol, $depth)
    {
        $depth = $depth * 2;
        $buyOrders = $this->getTopBuyOrders($symbol, $depth);
        $sellOrders = $this->getTopSellOrders($symbol, $depth);

        return [
            'lastUpdateId' => $this->getLastUpdateId(),
            'bids' => $buyOrders,
            'asks' => $sellOrders,
        ];
    }

    private function getTopBuyOrders($symbol, $depth)
    {
        $key = "buy_orders_$symbol";

        $data = Redis::zrevrange($key, 0, $depth - 1, 'WITHSCORES');

        $buys = [];

        for ($i = 0; $i < count($data); $i += 2) {
            $price = $data[$i];
            $quantity = $data[$i + 1];
            $buys[] = [$price, $quantity];
        }

        return $buys;
    }

    private function getTopSellOrders($symbol, $depth)
    {
        $key = "sell_orders_$symbol";

        $data = Redis::zrange($key, 0, $depth - 1, 'WITHSCORES');

        $sells = [];

        for ($i = 0; $i < count($data); $i += 2) {
            $price = $data[$i];
            $quantity = $data[$i + 1];
            $sells[] = [$price, $quantity];
        }

        return $sells;
    }

    public function getLastUpdateId()
    {
        return $this->orderRepository->getLastUpdateId();
    }

    public function placeOrder($order)
    {
        $this->orderRepository->create($order);
        $this->updateRedisOrderBook($order);
    }

    public function cancelOrder($order)
    {
        $this->orderRepository->delete($order['order_id']);
        $this->removeOrderFromRedis($order);
    }

    private function removeOrderFromRedis($order)
    {
        $symbol = $order['symbol'];
        $price = $order['price'];
        $quantity = $order['quantity'];

        $key = ($order['side'] === 'buy') ? "buy_orders_$symbol" : "sell_orders_$symbol";
        // Check if the price exists in the sorted set
        $existingPriceQuantity = Redis::zscore($key, $price);

        if ($existingPriceQuantity !== false) {
            // Price exists, update the quantity by subtracting the new quantity
            $newQuantity = $existingPriceQuantity - $quantity;

            // If the new quantity is <= 0, remove the price from the sorted set
            if ($newQuantity <= 0) {
                Redis::zrem($key, $price);
            } else {
                // Update the quantity in the sorted set
                Redis::zadd($key, [$price => $newQuantity]);
            }
        }
    }
}
