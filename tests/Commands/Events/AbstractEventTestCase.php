<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands\Events;

use Illuminate\Support\Str;
use Interop\Amqp\AmqpQueue as Queue;
use Interop\Amqp\AmqpTopic as Exchange;
use Enqueue\AmqpExt\AmqpContext as Connection;
use AvtoDev\AmqpRabbitManager\Tests\AbstractTestCase;

abstract class AbstractEventTestCase extends AbstractTestCase
{
    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var Exchange
     */
    protected $exchange;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $some_id;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->some_id    = Str::random();
        $this->queue      = new \Interop\Amqp\Impl\AmqpQueue('name');
        $this->exchange   = new \Interop\Amqp\Impl\AmqpTopic('name');
        $this->connection = (new \Enqueue\AmqpExt\AmqpConnectionFactory([
            'host' => '8.8.8.8',
        ]))->createContext();
    }

    /**
     * Test event constructor and public properties.
     *
     * @return void
     */
    abstract public function testConstructorAndProperties(): void;
}
