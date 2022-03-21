<?php

namespace LaraAssistant\LaraKafka;

use RdKafka\Conf;
use RdKafka\KafkaConsumer as BaseRdKafkaConsumer;

class RdKafkaConsumer
{
    // too high may cause kafka broker to run out of heap mem (MAX_BYTES * partition_count_in_topic = required mem)w
    public const MESSAGE_MAX_BYTES = 8 * 2 ** 20; // 8MB

    public function buildMultiTopicConsumer(
        array $topics,
        string $groupId,
        string $broker, 
        array $confProperties = []
    ): BaseRdKafkaConsumer {
        $consumer = new BaseRdKafkaConsumer($this->consumerConfig($groupId, $broker, $confProperties));
        $consumer->subscribe($topics);

        return $consumer;
    }

    /**
     * @param string $groupId
     * @param array $confProperties
     * @return Conf
     * @throws \RdKafka\Exception
     */
    private function consumerConfig(
        string $groupId,
        string $broker, 
        array $confProperties
    ): Conf {
        $conf = new Conf();
        $conf->set('group.id', $groupId);
        isset($confProperties['client.id']) && $conf->set('client.id', $confProperties['client.id']);
        $conf->set('metadata.broker.list', $broker);
        $conf->set('message.max.bytes', self::MESSAGE_MAX_BYTES);
        $conf->set('queued.max.messages.kbytes', 64 << 10);
        $conf->set('fetch.wait.max.ms', 10000);
        $conf->set('fetch.min.bytes', 32 << 20);
        $conf->set('heartbeat.interval.ms', 30000);
        $conf->set('session.timeout.ms', 30000 * 3); // heartbeat.interval.ms * 3
        $conf->set('socket.timeout.ms', 100000); // session.timeout.ms + 10s
        $conf->set('auto.commit.interval.ms', 12000);
        $conf->set('enable.partition.eof', 1);
        $this->applyConfProperties($conf, $confProperties);

        return $conf;
    }

    protected function applyConfProperties($conf, array $confProperties)
    {
        foreach ($confProperties as $property => $value) {
            $conf->set($property, $value);
        }
    }
}
