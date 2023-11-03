<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands\Events;

use AvtoDev\AmqpRabbitManager\Commands\Events\QueueCreated;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Commands\Events\QueueCreated
 */
class QueueCreatedTest extends AbstractEventTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testConstructorAndProperties(): void
    {
        $event = new QueueCreated($this->connection, $this->queue, $this->some_id);

        $this->assertSame($this->connection, $event->connection);
        $this->assertSame($this->queue, $event->queue);
        $this->assertSame($this->some_id, $event->queue_id);
    }
}
