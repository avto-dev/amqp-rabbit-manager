<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Commands\Events;

use Interop\Amqp\AmqpTopic as Exchange;
use Enqueue\AmqpExt\AmqpContext as Connection;

final class ExchangeCreating
{
    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var Exchange
     */
    public $exchange;

    /**
     * Create a new event instance.
     *
     * @param Connection $connection
     * @param Exchange   $exchange
     */
    public function __construct(Connection $connection, Exchange $exchange)
    {
        $this->connection = $connection;
        $this->exchange   = $exchange;
    }
}
