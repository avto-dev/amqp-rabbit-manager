<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands\Events;

use AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeCreated;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeCreated<extended>
 */
class ExchangeCreatedTest extends AbstractEventTestCase
{
    /**
     * {@inheritDoc}
     */
    public function testConstructorAndProperties(): void
    {
        $event = new ExchangeCreated($this->connection, $this->exchange);

        $this->assertSame($this->connection, $event->connection);
        $this->assertSame($this->exchange, $event->exchange);
    }
}
