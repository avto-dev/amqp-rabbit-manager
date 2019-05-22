<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands\Events;

use AvtoDev\AmqpRabbitManager\Commands\Events\QueueCreating;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Commands\Events\QueueCreating<extended>
 */
class QueueCreatingTest extends AbstractEventTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testConstructorAndProperties(): void
    {
        $event = new QueueCreating($this->connection, $this->queue, $this->some_id);

        $this->assertSame($this->connection, $event->connection);
        $this->assertSame($this->queue, $event->queue);
        $this->assertSame($this->some_id, $event->queue_id);
    }
}
