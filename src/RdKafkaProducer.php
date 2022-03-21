<?php

namespace LaraAssistant\LaraKafka;

use RdKafka\Conf;
use RdKafka\Exception;
use RdKafka\Producer;

class RdKafkaProducer
{
    //too high may cause kafka broker to run out of heap mem (MAX_BYTES * partition_count_in_topic = required mem)w
    public const MESSAGE_MAX_BYTES = 8 * 2 ** 20; // 8MB

    private string $brokers;

    public function __construct(string $brokers)
    {
        $this->brokers = $brokers;
    }

    /**
     * @param mixed[] $config
     * @return Producer
     * @throws Exception
     */
    public function buildProducer(array $config = []): Producer
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', $this->brokers);
        $conf->set('message.max.bytes', self::MESSAGE_MAX_BYTES);
        $conf->set('topic.metadata.refresh.interval.ms', 10000);

        $conf->set('linger.ms', $config['linger.ms'] ?? 5000); // alias: queue.buffering.max.ms

        if (isset($config['message.send.max.retries'])) {
            $conf->set('message.send.max.retries', $config['message.send.max.retries']);
        }
        if (isset($config['message.timeout.ms'])) {
            $conf->set('message.timeout.ms', $config['message.timeout.ms']);
        }

        $conf->set('request.required.acks', 1);
        $conf->set('partitioner', $config['partitioner'] ?? 'murmur2');
        $conf->set('compression.codec', $config['compression.codec'] ?? 'gzip');
        $conf->set('queue.buffering.max.messages', $config['queue.buffering.max.messages'] ?? 100000);

        $producer = new Producer($conf);
        $producer->addBrokers($this->brokers);

        return $producer;
    }
}
