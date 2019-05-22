<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands\Events;

use AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeDeleted;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeDeleted<extended>
 */
class ExchangeDeletedTest extends AbstractEventTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testConstructorAndProperties(): void
    {
        $event = new ExchangeDeleted($this->connection, $this->exchange);

        $this->assertSame($this->connection, $event->connection);
        $this->assertSame($this->exchange, $event->exchange);
    }
}
