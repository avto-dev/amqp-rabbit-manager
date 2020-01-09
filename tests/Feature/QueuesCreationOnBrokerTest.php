<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Feature;

use Throwable;
use Illuminate\Support\Str;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use AvtoDev\AmqpRabbitManager\QueuesFactoryInterface;
use AvtoDev\AmqpRabbitManager\Tests\AbstractTestCase;
use AvtoDev\AmqpRabbitManager\ExchangesFactoryInterface;
use AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface;

/**
 * @group feature
 *
 * @coversNothing
 * @group usesExternalServices
 */
class QueuesCreationOnBrokerTest extends AbstractTestCase
{
    /**
     * @var ConnectionsFactoryInterface
     */
    protected $connections;

    /**
     * @var QueuesFactoryInterface
     */
    protected $queues;

    /**
     * @var ExchangesFactoryInterface
     */
    protected $exchanges;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connections = $this->app->make(ConnectionsFactoryInterface::class);
        $this->queues      = $this->app->make(QueuesFactoryInterface::class);
        $this->exchanges   = $this->app->make(ExchangesFactoryInterface::class);

        $this->unsetBroker();
    }

    /**
     * @medium
     *
     * @throws Throwable
     *
     * @return void
     */
    public function testQueuesCreation(): void
    {
        $this->connections->addFactory($connection_name = 'foo', [
            'host'  => env('RABBIT_HOST', 'rabbitmq'),
            'port'  => (int) env('RABBIT_PORT', 5672),
            'vhost' => env('RABBIT_VHOST', '/'),
            'user'  => env('RABBIT_LOGIN', 'guest'),
            'pass'  => env('RABBIT_PASSWORD', 'guest'),
        ]);
        $connection = $this->connections->make($connection_name);

        $this->queues->addFactory($queue_id = 'bar', [
            'name'         => 'queue-name',
            'flags'        => AmqpQueue::FLAG_NOPARAM,
            'arguments'    => [
                'x-message-ttl'  => 604800000,
                'x-queue-mode'   => 'lazy',
                'x-max-priority' => 255,
            ],
            'consumer_tag' => null,
        ]);
        $queue = $this->queues->make($queue_id);

        $this->exchanges->addFactory($exchange_id = 'baz', [
            'name'      => 'exchange-name',
            'type'      => AmqpTopic::TYPE_DIRECT,
            'flags'     => AmqpTopic::FLAG_DURABLE,
            'arguments' => [],
        ]);
        $exchange = $this->exchanges->make($exchange_id);

        // Create queue
        $this->assertSame(0, $connection->declareQueue($queue));
        $connection->declareTopic($exchange);

        // Any messages, sent to the exchange should be delivered to the queue
        $connection->bind(new AmqpBind($queue, $exchange));

        // Send message into the queue
        $connection->createProducer()->send($queue, $connection->createMessage($message_1_body = Str::random(64)));
        $connection->createProducer()->send($exchange, $connection->createMessage($message_2_body = Str::random(64)));

        \usleep(200000); // 0.2 sec

        // Create consumer and get first message back
        $consumer  = $connection->createConsumer($queue);
        $message_1 = $consumer->receive(200);

        // Mark message as acknowledged
        $consumer->acknowledge($message_1);

        // Get second message
        $message_2 = $consumer->receive(200);

        // Mark message as acknowledged
        $consumer->acknowledge($message_2);

        // Assert message content
        $this->assertSame($message_1_body, $message_1->getBody());
        $this->assertSame($message_2_body, $message_2->getBody());

        // Remove queue
        $connection->deleteQueue($queue);
        $connection->deleteTopic($exchange);
    }
}
