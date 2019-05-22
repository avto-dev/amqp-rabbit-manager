<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Commands\Events;

use Interop\Amqp\AmqpQueue as Queue;
use Enqueue\AmqpExt\AmqpContext as Connection;

final class QueueDeleted
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
     * @var string
     */
    public $queue_id;

    /**
     * Create a new event instance.
     *
     * @param Connection $connection
     * @param Queue      $queue
     * @param string     $queue_id
     */
    public function __construct(Connection $connection, Queue $queue, string $queue_id)
    {
        $this->connection = $connection;
        $this->queue      = $queue;
        $this->queue_id   = $queue_id;
    }
}
