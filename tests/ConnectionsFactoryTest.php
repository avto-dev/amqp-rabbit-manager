<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests;

use Illuminate\Support\Str;
use Enqueue\AmqpExt\AmqpContext;
use AvtoDev\AmqpRabbitManager\ConnectionsFactory;
use AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface;
use AvtoDev\AmqpRabbitManager\Exceptions\FactoryException;

/**
 * @covers \AvtoDev\AmqpRabbitManager\ConnectionsFactory<extended>
 */
class ConnectionsFactoryTest extends AbstractTestCase
{
    /**
     * @var ConnectionsFactory
     */
    protected $factory;

    /**
     * @var array[]
     */
    protected $connections_settings = [
        'conn-1' => [
            'host' => 'localhost',
            'port' => 5672,
        ],
        'conn-2' => [
            'host' => 'remotehost',
            'port' => 2765,
        ],
    ];

    /**
     * @var array
     */
    protected $connection_defaults = [
        'user' => 'evil',
        'pass' => 'live',
    ];

    /**
     * @var string
     */
    protected $default = 'conn-1';

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new ConnectionsFactory(
            $this->connections_settings,
            $this->connection_defaults,
            $this->default
        );
    }

    /**
     * @return void
     */
    public function testInstanceOf(): void
    {
        $this->assertInstanceOf(ConnectionsFactoryInterface::class, $this->factory);
    }

    /**
     * @return void
     */
    public function testNamesGetter(): void
    {
        $this->assertSame(\array_keys($this->connections_settings), $this->factory->names());
    }

    /**
     * @return void
     */
    public function testConfigsPassingIntoDriverAndDefaultSettingsSet(): void
    {
        $this->assertSame(
            $this->connections_settings[$name_1 = 'conn-1']['host'], $this->factory->configuration($name_1)->getHost()
        );
        $this->assertSame(
            $this->connections_settings[$name_1]['port'], $this->factory->configuration($name_1)->getPort()
        );
        $this->assertSame(
            $this->connection_defaults['user'], $this->factory->configuration($name_1)->getUser()
        );
        $this->assertSame(
            $this->connection_defaults['pass'], $this->factory->configuration($name_1)->getPass()
        );

        $this->assertSame(
            $this->connections_settings[$name_2 = 'conn-2']['host'], $this->factory->configuration($name_2)->getHost()
        );
        $this->assertSame(
            $this->connections_settings[$name_2]['port'], $this->factory->configuration($name_2)->getPort()
        );
        $this->assertSame(
            $this->connection_defaults['user'], $this->factory->configuration($name_2)->getUser()
        );
        $this->assertSame(
            $this->connection_defaults['pass'], $this->factory->configuration($name_2)->getPass()
        );
    }

    /**
     * @return void
     */
    public function testDefaultContextGetter(): void
    {
        $this->assertEquals($this->factory->default(), $this->factory->make($this->default));
    }

    /**
     * @return void
     */
    public function testEThrowingExceptionWhenRequiredConfigForUnknownConnection(): void
    {
        $this->expectException(FactoryException::class);

        $this->factory->configuration(Str::random());
    }

    /**
     * @return void
     */
    public function testThrowingExceptionWhenRequestedUnknownStore(): void
    {
        $this->expectException(FactoryException::class);

        $this->factory->make(Str::random());
    }

    /**
     * @return void
     */
    public function testExceptionThrownWhenDefaultDriverIsNotSet(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessageMatches('~default.*not.*set~i');

        $this->factory = new ConnectionsFactory($this->connections_settings, $this->connection_defaults);

        $this->factory->default();
    }

    /**
     * @small
     *
     * @return void
     */
    public function testAddAndRemoveFactory(): void
    {
        $this->factory->addFactory($connection_name = 'conn', []);
        $this->assertContains($connection_name, $this->factory->names());

        $this->assertInstanceOf(AmqpContext::class, $this->factory->make($connection_name));

        $this->factory->removeFactory($connection_name);
        $this->assertNotContains($connection_name, $this->factory->names());
    }
}
