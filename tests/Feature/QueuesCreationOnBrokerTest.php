<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Feature;

use Illuminate\Support\Str;
use Interop\Amqp\AmqpQueue;
use AvtoDev\AmqpRabbitManager\QueuesFactoryInterface;
use AvtoDev\AmqpRabbitManager\Tests\AbstractTestCase;
use AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface;

/**
 * @group feature
 *
 * @coversNothing
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
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connections = $this->app->make(ConnectionsFactoryInterface::class);
        $this->queues      = $this->app->make(QueuesFactoryInterface::class);

        $this->deleteAllQueues();
    }

    /**
     * @medium
     *
     * @return void
     */
    public function testQueuesCreation(): void
    {
        $this->connections->addFactory($connection_name = 'foo', [
            'host'     => env('RABBIT_HOST', 'rabbitmq'),
            'port'     => (int) env('RABBIT_PORT', 5672),
            'vhost'    => env('RABBIT_VHOST', '/'),
            'login'    => env('RABBIT_LOGIN', 'guest'),
            'password' => env('RABBIT_PASSWORD', 'guest'),
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

        // Create queue
        $this->assertSame(0, $connection->declareQueue($queue));

        // Send message into the queue
        $connection->createProducer()->send($queue, $connection->createMessage($message_body = 'baz' . Str::random()));

        \usleep(200000); // 0.2 sec

        // Create consumer and get message back
        $consumer = $connection->createConsumer($queue);
        $message  = $consumer->receive(200);

        // Mark message as acknowledged
        $consumer->acknowledge($message);

        // Assert message content
        $this->assertSame($message_body, $message->getBody());

        // Remove queue
        $connection->deleteQueue($queue);
    }
}
