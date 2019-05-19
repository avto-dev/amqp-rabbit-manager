<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests;

use Enqueue\AmqpExt\AmqpContext;
use Interop\Amqp\Impl\AmqpQueue;
use AvtoDev\AmqpRabbitManager\QueuesFactory;
use AvtoDev\AmqpRabbitManager\ServiceProvider;
use AvtoDev\AmqpRabbitManager\ConnectionsFactory;
use AvtoDev\AmqpRabbitManager\QueuesFactoryInterface;
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
    protected $manager;

    /**
     * @var QueuesFactory
     */
    protected $factory;

    /**
     * @var string
     */
    protected $root;

    /**
     * @return void
     */
    public function testDiRegistration(): void
    {
        $this->assertInstanceOf(ConnectionsFactory::class, $this->manager);
        $this->assertInstanceOf(QueuesFactory::class, $this->factory);
        $this->assertInstanceOf(RabbitSetupCommand::class, $this->app->make('command.rabbit.setup'));
    }

    /**
     * @return void
     */
    public function testCorrectNamesResolving(): void
    {

        $this->assertSame(\array_keys($this->config()->get("{$this->root}.connections")), $this->manager->names());
        $this->assertSame(\array_keys($this->config()->get("{$this->root}.queues")), $this->factory->ids());
    }

    /**
     * @return void
     */
    public function testConnectionsCreating(): void
    {
        foreach (\array_keys($this->config()->get("{$this->root}.connections")) as $name) {
            $this->assertInstanceOf(AmqpContext::class, $this->manager->make($name));
        }
    }

    /**
     * @return void
     */
    public function testQueuesCreating(): void
    {
        foreach (\array_keys($this->config()->get("{$this->root}.queues")) as $name) {
            $this->assertInstanceOf(AmqpQueue::class, $this->factory->make($name));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->app->make(ConnectionsFactoryInterface::class);
        $this->factory = $this->app->make(QueuesFactoryInterface::class);
        $this->root    = ServiceProvider::getConfigRootKeyName();
    }
}
