<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests;

use Enqueue\AmqpExt\AmqpContext;
use Interop\Amqp\Impl\AmqpQueue;
use AvtoDev\AmqpRabbitManager\QueuesFactory;
use AvtoDev\AmqpRabbitManager\ServiceProvider;
use AvtoDev\AmqpRabbitManager\ExchangesFactory;
use AvtoDev\AmqpRabbitManager\ConnectionsFactory;
use AvtoDev\AmqpRabbitManager\QueuesFactoryInterface;
use AvtoDev\AmqpRabbitManager\ExchangesFactoryInterface;
use AvtoDev\AmqpRabbitManager\Commands\RabbitSetupCommand;
use AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface;

/**
 * @covers \AvtoDev\AmqpRabbitManager\ServiceProvider<extended>
 */
class ServiceProviderTest extends AbstractTestCase
{
    /**
     * @var ConnectionsFactoryInterface
     */
    protected $connections;

    /**
     * @var QueuesFactory
     */
    protected $queues;

    /**
     * @var ExchangesFactory
     */
    protected $exchanges;

    /**
     * @var string
     */
    protected $root;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connections = $this->app->make(ConnectionsFactoryInterface::class);
        $this->queues      = $this->app->make(QueuesFactoryInterface::class);
        $this->exchanges   = $this->app->make(ExchangesFactoryInterface::class);
        $this->root        = ServiceProvider::getConfigRootKeyName();
    }

    /**
     * @return void
     */
    public function testDiRegistration(): void
    {
        $this->assertInstanceOf(ConnectionsFactory::class, $this->connections);
        $this->assertInstanceOf(QueuesFactory::class, $this->queues);
        $this->assertInstanceOf(ExchangesFactory::class, $this->exchanges);
        $this->assertInstanceOf(RabbitSetupCommand::class, $this->app->make('command.rabbit.setup'));
    }

    /**
     * @return void
     */
    public function testCorrectNamesResolving(): void
    {
        $this->assertSame(\array_keys($this->config()->get("{$this->root}.connections")), $this->connections->names());
        $this->assertSame(\array_keys($this->config()->get("{$this->root}.queues")), $this->queues->ids());
        $this->assertSame(\array_keys($this->config()->get("{$this->root}.queues")), $this->queues->ids());
    }

    /**
     * @return void
     */
    public function testConnectionsCreating(): void
    {
        foreach (\array_keys($this->config()->get("{$this->root}.connections")) as $name) {
            $this->assertInstanceOf(AmqpContext::class, $this->connections->make($name));
        }
    }

    /**
     * @return void
     */
    public function testQueuesCreating(): void
    {
        foreach (\array_keys($this->config()->get("{$this->root}.queues")) as $name) {
            $this->assertInstanceOf(AmqpQueue::class, $this->queues->make($name));
        }
    }
}
