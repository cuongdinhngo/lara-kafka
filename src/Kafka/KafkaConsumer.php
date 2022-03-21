<?php

namespace App\Libraries\Kafka;

use RdKafka\KafkaConsumer as BaseRdKafkaConsumer;
use LaraAssistant\LaraKafka\RdKafkaConsumer;

class KafkaConsumer
{
    private bool $check = true;

    protected BaseRdKafkaConsumer $kafkaConsumer;

    public function consume()
    {
        while ($this->check) {
            $message = $this->kafkaConsumer->consume(1200);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $message->err) {
                $this->handleMessage($message);
            } else {
                $this->handleErrors($message);
            }
        }
    }

    public function init(array $topics, string $groupId, string $broker, array $confProperties = [])
    {
        $this->kafkaConsumer = (new RdKafkaConsumer())
                                ->buildMultiTopicConsumer($topics, $groupId, $broker, $confProperties);
    }

    protected function handleMessage($message)
    {
        dump($message);
    }

    protected function handleErrors($message)
    {
        switch ($message->err) {
            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                dump("No more messages; will wait for more");
                break;
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                dump('Timed out: '.date('Y/m/d h:i:s', time()));
                break;
            default:
                throw new \Exception($message->errstr(), $message->err);
                break;
        }
    }
}