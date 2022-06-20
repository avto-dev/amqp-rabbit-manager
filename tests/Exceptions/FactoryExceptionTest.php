<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Exceptions;

use AvtoDev\AmqpRabbitManager\Tests\AbstractTestCase;
use AvtoDev\AmqpRabbitManager\Exceptions\FactoryException;

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
        $this->assertMatchesRegularExpression('~connection.*not exists~i', FactoryException::connectionNotExists('')->getMessage());
        $this->assertMatchesRegularExpression('~Default.*not set~i', FactoryException::defaultConnectionNotSet()->getMessage());
        $this->assertMatchesRegularExpression('~Queue.+name.*not set~i', FactoryException::queueNameNotSet('')->getMessage());
        $this->assertMatchesRegularExpression('~Queue.*not exists~i', FactoryException::queueNotExists('')->getMessage());
        $this->assertMatchesRegularExpression('~Exchange.+name.*not set~i', FactoryException::exchangeNameNotSet('')->getMessage());
        $this->assertMatchesRegularExpression('~Exchange.*not exists~i', FactoryException::exchangeNotExists('')->getMessage());
    }
}
