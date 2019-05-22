<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands\Events;

use AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeDeleting;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeDeleting<extended>
 */
class ExchangeDeletingTest extends AbstractEventTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testConstructorAndProperties(): void
    {
        $event = new ExchangeDeleting($this->connection, $this->exchange);

        $this->assertSame($this->connection, $event->connection);
        $this->assertSame($this->exchange, $event->exchange);
    }
}
