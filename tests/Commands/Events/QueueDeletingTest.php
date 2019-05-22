<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands\Events;

use AvtoDev\AmqpRabbitManager\Commands\Events\QueueDeleting;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Commands\Events\QueueDeleting<extended>
 */
class QueueDeletingTest extends AbstractEventTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testConstructorAndProperties(): void
    {
        $event = new QueueDeleting($this->connection, $this->queue);

        $this->assertSame($this->connection, $event->connection);
        $this->assertSame($this->queue, $event->queue);
    }
}
