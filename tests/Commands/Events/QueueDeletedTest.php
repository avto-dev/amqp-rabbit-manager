<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands\Events;

use AvtoDev\AmqpRabbitManager\Commands\Events\QueueDeleted;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Commands\Events\QueueDeleted<extended>
 */
class QueueDeletedTest extends AbstractEventTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testConstructorAndProperties(): void
    {
        $event = new QueueDeleted($this->connection, $this->queue, $this->some_id);

        $this->assertSame($this->connection, $event->connection);
        $this->assertSame($this->queue, $event->queue);
        $this->assertSame($this->some_id, $event->queue_id);
    }
}
