<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands\Events;

use AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeCreating;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeCreating<extended>
 */
class ExchangeCreatingTest extends AbstractEventTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testConstructorAndProperties(): void
    {
        $event = new ExchangeCreating($this->connection, $this->exchange, $this->some_id);

        $this->assertSame($this->connection, $event->connection);
        $this->assertSame($this->exchange, $event->exchange);
        $this->assertSame($this->some_id, $event->exchange_id);
    }
}
