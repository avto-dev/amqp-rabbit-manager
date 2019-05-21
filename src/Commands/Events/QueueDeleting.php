<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Commands\Events;

use Interop\Amqp\AmqpQueue as Queue;
use Enqueue\AmqpExt\AmqpContext as Connection;

class QueueDeleting
{
    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var Queue
     */
    public $queue;

    /**
     * Create a new event instance.
     *
     * @param Connection $connection
     * @param Queue      $queue
     */
    public function __construct(Connection $connection, Queue $queue)
    {
        $this->connection = $connection;
        $this->queue      = $queue;
    }
}
