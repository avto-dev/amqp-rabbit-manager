<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands\Events;

use AvtoDev\AmqpRabbitManager\Commands\Events\QueueCreated;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Commands\Events\QueueCreated<extended>
 */
class QueueCreatedTest extends AbstractEventTestCase
{
    /**
     * {@inheritDoc}
     */
    public function testConstructorAndProperties(): void
    {
        $event = new QueueCreated($this->connection, $this->queue);

        $this->assertSame($this->connection, $event->connection);
        $this->assertSame($this->queue, $event->queue);
    }
}
