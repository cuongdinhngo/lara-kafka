<?php

namespace App\Libraries\Kafka;

use Illuminate\Support\Facades\Log;
use LaraAssistant\LaraKafka\RdKafkaProducer;

class KafkaProducer
{
    private const MAX_PRODUCER_MESSAGES_QUEUE = 10000;
    private const MAX_ERROR_LOGS = 10;

    private Producer $producer;

    private ProducerTopic $topicProducer;

    private string $partition = 'unset';

    private int $queue = 0;
    private int $errorLogs = 0;

    /** @var string[][] */
    private array $aliasesRoutesMapper;

    private string $brokers;

    private string $topic;

    public function __construct(string $topic)
    {
        $this->topic = $topic;
    }

    public function init(): void
    {
        try {
            $kafka = new RdKafkaProducer($this->brokers);
            $this->producer = $kafka->buildProducer([
                'compression.codec' => 'none',
                'message.timeout.ms' => 10000,
                'partitioner' => 'murmur2_random',
                'message.send.max.retries' => 3,
            ]);
            $this->topicProducer = $this->producer->newTopic($this->topic);
        } catch (\Throwable $e) {
            $this->errorLogs++;
            Log::error($e);
        }
    }

    public function setBrokers(string $brokers): void
    {
        $this->brokers = $brokers;
    }

    /**
     * @param string $row
     * @throws \Exception
     */
    public function addPayload(string $payload): void
    {
        if ($this->errorLogs > 0) {
            return;
        }

        try {
            $this->topicProducer->produce(RD_KAFKA_PARTITION_UA, 0, $payload);
            $this->producer->poll(50);
            $result = $this->producer->flush(10000);

            if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
                $this->errorLogs++;
                Log::error(rd_kafka_err2str($result));
            }
        } catch (\Throwable $e) {
            $this->errorLogs++;
            Log::error($e);
        }
    }

    public function finalize(): void
    {
        if ($this->errorLogs > 0) {
            return;
        }

        try {
            $outQLen = $this->producer->getOutQLen();

            while ($this->producer->getOutQLen()) {
                if (extension_loaded('pcntl')) {
                    pcntl_signal_dispatch();
                }
                $this->producer->poll(50);
                $result = $this->producer->flush(10000);

                if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
                    $this->errorLogs++;
                    Log::error(rd_kafka_err2str($result));
                    break;
                }
            }
        } catch (\Throwable $e) {
            $this->errorLogs++;
            Log::error($e);
        }
    }

    public function setPartition(string $partition): void
    {
        $this->partition = $partition;
    }
}
