<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests;

use Illuminate\Support\Str;
use Interop\Amqp\AmqpTopic;
use AvtoDev\AmqpRabbitManager\ExchangesFactory;
use AvtoDev\AmqpRabbitManager\ExchangesFactoryInterface;
use AvtoDev\AmqpRabbitManager\Exceptions\FactoryException;

/**
 * @covers \AvtoDev\AmqpRabbitManager\ExchangesFactory
 */
class ExchangesFactoryTest extends AbstractTestCase
{
    /**
     * @var ExchangesFactory
     */
    protected $factory;

    /**
     * @var mixed[]
     */
    protected $exchanges_declaration = [
        'exchange-1' => [
            'name'      => 'exchange-1-name',
            'type'      => AmqpTopic::TYPE_DIRECT,
            'flags'     => AmqpTopic::FLAG_DURABLE,
            'arguments' => [],
        ],
        'exchange-2' => [
            'name'      => 'exchange-2-name',
            'type'      => AmqpTopic::TYPE_HEADERS,
            'flags'     => AmqpTopic::FLAG_AUTODELETE,
            'arguments' => ['bar' => 'baz'],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ExchangesFactory($this->exchanges_declaration);
    }

    /**
     * @return void
     */
    public function testInstanceOf(): void
    {
        $this->assertInstanceOf(ExchangesFactoryInterface::class, $this->factory);
    }

    /**
     * @return void
     */
    public function testMakeWithValidValues(): void
    {
        $exchange_1 = $this->factory->make($exchange_1_name = 'exchange-1');
        $exchange_2 = $this->factory->make($exchange_2_name = 'exchange-2');

        foreach ([$exchange_1_name => $exchange_1, $exchange_2_name => $exchange_2] as $name => $exchange) {
            /* @var $exchange AmqpTopic */
            $this->assertSame($this->exchanges_declaration[$name]['name'], $exchange->getTopicName());
            $this->assertSame($this->exchanges_declaration[$name]['type'], $exchange->getType());
            $this->assertSame($this->exchanges_declaration[$name]['flags'], $exchange->getFlags());
            $this->assertSame($this->exchanges_declaration[$name]['arguments'], $exchange->getArguments());
        }
    }

    /**
     * @return void
     */
    public function testNamesGetter(): void
    {
        $this->assertSame(\array_keys($this->exchanges_declaration), $this->factory->ids());
    }

    /**
     * @return void
     */
    public function testExceptionThrownWhenPassedWrongExchangeName(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessageMatches('~exchange.*not.?exists~i');

        $this->factory->make(Str::random());
    }

    /**
     * @return void
     */
    public function testExceptionThrownWhenExchangeDeclaredWithoutName(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessageMatches('~exchange.*not.?set~i');

        $this->factory = new ExchangesFactory([
            'foo' => [
                //'name'       => 'exchange-2-name',
                'type'      => AmqpTopic::TYPE_DIRECT,
                'flags'     => AmqpTopic::FLAG_DURABLE,
                'arguments' => [],
            ],
        ]);

        $this->factory->make('foo');
    }

    /**
     * @return void
     */
    public function testExceptionThrownWhenExchangeDeclaredWithEmptyName(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessageMatches('~exchange.*not.?set~i');

        $this->factory = new ExchangesFactory([
            'foo' => [
                'name'      => '',
                'type'      => AmqpTopic::TYPE_DIRECT,
                'flags'     => AmqpTopic::FLAG_DURABLE,
                'arguments' => [],
            ],
        ]);

        $this->factory->make('foo');
    }

    /**
     * @small
     *
     * @return void
     */
    public function testAddAndRemoveFactory(): void
    {
        $this->factory->addFactory($exchange_id = 'exchange-id', ['name' => 'some-exchange']);
        $this->assertContains($exchange_id, $this->factory->ids());

        $this->assertInstanceOf(AmqpTopic::class, $this->factory->make($exchange_id));

        $this->factory->removeFactory($exchange_id);
        $this->assertNotContains($exchange_id, $this->factory->ids());
    }
}
