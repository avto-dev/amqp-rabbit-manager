<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Exceptions;

use AvtoDev\AmqpRabbitManager\Exceptions\FactoryException;
use AvtoDev\AmqpRabbitManager\Tests\AbstractTestCase;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Exceptions\FactoryException<extended>
 */
class FactoryExceptionTest extends AbstractTestCase
{
    /**
     * @return void
     */
    public function testImplementation(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new FactoryException);
    }

    /**
     * @return void
     */
    public function testStaticFabrics(): void
    {
        $this->assertRegExp('~connection.*not exists~i', FactoryException::connectionNotExists('')->getMessage());
        $this->assertRegExp('~Default.*not set~i', FactoryException::defaultConnectionNotSet()->getMessage());
        $this->assertRegExp('~Queue.+name.*not set~i', FactoryException::queueNameNotSet('')->getMessage());
        $this->assertRegExp('~Queue.*not exists~i', FactoryException::queueNotExists('')->getMessage());
        $this->assertRegExp('~Exchange.+name.*not set~i', FactoryException::exchangeNameNotSet('')->getMessage());
        $this->assertRegExp('~Exchange.*not exists~i', FactoryException::exchangeNotExists('')->getMessage());
    }
}
