<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Commands\Events;

use Interop\Amqp\AmqpTopic as Exchange;
use Enqueue\AmqpExt\AmqpContext as Connection;

final class ExchangeDeleted
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
     * @var string
     */
    public $exchange_id;

    /**
     * Create a new event instance.
     *
     * @param Connection $connection
     * @param Exchange   $exchange
     * @param string     $exchange_id
     */
    public function __construct(Connection $connection, Exchange $exchange, string $exchange_id)
    {
        $this->connection  = $connection;
        $this->exchange    = $exchange;
        $this->exchange_id = $exchange_id;
    }
}
