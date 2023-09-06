<?php

namespace App\Console\Commands;

use RdKafka\Conf;
use RdKafka\Producer;
use Illuminate\Console\Command;

class ProduceKafkaStreams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'produce:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Produce order data for kafka';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $conf = new Conf();
        $conf->set('bootstrap.servers', 'localhost:9092');
        $conf->set('metadata.broker.list', 'localhost:9092');

        $producer = new Producer($conf);
        $producer->addBrokers("localhost:9092");
        $topic = $producer->newTopic("new-order");

        $symbols = ['BTCUSDT', 'ETHUSDT', 'XRPUSDT', 'BNBUSDT', 'LTCUSDT', 'ADAUSDT'];

        for ($i = 0; $i < 1000; $i++) {
            $orderData = $this->generateRandomOrder($symbols);
            $orderJson = json_encode($orderData);
            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $orderJson);
            $producer->poll(0);
        }

        $this->flushProducer($producer);
    }

    private function generateRandomOrder(array $symbols): array
    {
        return [
            'order_id' => rand(1, 1000000),
            'symbol' => $symbols[array_rand($symbols)],
            'quantity' => round(mt_rand(0, 1000000) / 1000000 * 1000, 6),
            'price' => round(mt_rand(4000000, 500000000) / 10000, 6),
            'action' => rand(0, 1) ? 'buy' : 'sell',
        ];
    }

    private function flushProducer(Producer $producer)
    {
        for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
            $result = $producer->flush(10000);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                return;
            }
        }

        throw new \RuntimeException('Was unable to flush, messages might be lost!');
    }
}
