# Lara Kafka Client

Laravel Kafka


1 - Install `cuongdinhngo/lara-kafka` using Composer.

```php
$ composer require cuongdinhngo/lara-kafka
```

2 - Add the following service provider in `config/app.php`

```php
    /*
     * Package Service Providers...
     */
    LaraAssistant\LaraKafka\LaraKafkaServiceProvider::class,

```

This command copies Libraries/Kafka folder into app that contains KafkaConsumer & KafkaProducer classes

3 - Create a topic

You must start Zookeeper and Kafka service, [please read more detail about how to install kafka](https://www.digitalocean.com/community/tutorials/how-to-install-apache-kafka-on-ubuntu-20-04)

```php
~/kafka/bin/kafka-topics.sh --create --zookeeper localhost:2181 --replication-factor 1 --partitions 1 --topic TutorialTopic
```

4 - Create a message

```php
use App\Libraries\Kafka\KafkaProducer;
use App\Libraries\Kafka\KafkaConsumer;

. . .
 
   public function testKafka()
   {
       dump(date('Y/m/d h:i:s', time()));
       $kafkProducer = new KafkaProducer("TutorialTopic");
       $kafkProducer->setBrokers("localhost:9092");
       $kafkProducer->init();
       $kafkProducer->addPayload("HELLO ... ".date('Y/m/d h:i:s', time()));
       dd(__METHOD__);
   }
```

Please member that message content must be string

5 - Consume a message

```php
   public function consumeKafka()
   {
       dump('start ...');
       dump(date('Y/m/d h:i:s', time()));
 
       $consumer = new KafkaConsumer();
       $consumer->init(
           ['TutorialTopic'],
           'myConsumerGroup',
           'localhost:9092',
           [
               'group.id' => 'myConsumerGroup',
               'auto.offset.reset' => 'earliest'
           ]
       );
      
       echo "Waiting for partition assignment... (make take some time when\n";
       echo "quickly re-joining the group after leaving it.)\n";
 
       $consumer->consume();
 
   }
```

You can modify Libraries/Kafka/KafkaConsumer class to handle message and error

```php
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
```
