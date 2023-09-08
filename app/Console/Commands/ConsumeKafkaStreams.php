<?php

namespace App\Console\Commands;

use App\Services\OrderBookService;
use RdKafka\Message;
use SplMaxHeap;
use SplMinHeap;
use Illuminate\Console\Command;

class ConsumeKafkaStreams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consume-order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume order data from kafka';

    private $orderBookService;

    public function __construct(OrderBookService $orderBookService)
    {
        parent::__construct();
        $this->orderBookService = $orderBookService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', config('kafka.broker'));
        $conf->set('group.id', 'orderGroup');
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.auto.commit', 'false');

        $consumer = new \RdKafka\KafkaConsumer($conf);

        $consumer->subscribe(['new-order', 'cancel-order']);

        echo "Waiting for partition assignment... (make take some time when\n";
        echo "quickly re-joining the group after leaving it.)\n";

        while (true) {
            $message = $consumer->consume(120 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->processMessage($message);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "Timed out\n";
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        }
    }

    /**
     * Process Kafka message
     *
     * @param \RdKafka\Message $kafkaMessage
     * @return void
     */
    protected function processMessage(Message $kafkaMessage)
    {
        $message = json_decode($kafkaMessage->payload, true);

        switch ($kafkaMessage->topic_name) {
            case 'new-order':
                $this->orderBookService->placeOrder($message);
                break;
            case 'cancel-order':
                $this->orderBookService->cancelOrder($message);
                break;
            default:
                echo "Received a message from an unsupported topic: {$kafkaMessage->topic_name}\n";
                break;
        }
    }
}
