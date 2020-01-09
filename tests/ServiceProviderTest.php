<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests;

use Enqueue\AmqpExt\AmqpContext;
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
     * @small
     *
     * @return void
     */
    public function testGetConfigRootKeyName(): void
    {
        $this->assertSame('rabbitmq', ServiceProvider::getConfigRootKeyName());
    }

    /**
     * @return void
     */
    public function testDiRegistration(): void
    {
        $this->assertInstanceOf(ConnectionsFactory::class, $this->connections);
        $this->assertSame($this->connections, $this->app->make(ConnectionsFactoryInterface::class)); // singleton?

        $this->assertInstanceOf(QueuesFactory::class, $this->queues);
        $this->assertSame($this->queues, $this->app->make(QueuesFactoryInterface::class)); // singleton?

        $this->assertInstanceOf(ExchangesFactory::class, $this->exchanges);
        $this->assertSame($this->exchanges, $this->app->make(ExchangesFactoryInterface::class)); // singleton?

        $this->assertInstanceOf(RabbitSetupCommand::class, $cmd = $this->app->make('command.rabbit.setup'));
        $this->assertSame($cmd, $this->app->make('command.rabbit.setup')); // singleton?
    }

    /**
     * @return void
     */
    public function testCorrectNamesResolving(): void
    {
        $this->assertSame(\array_keys($this->config()->get("{$this->root}.connections")), $this->connections->names());
        $this->assertSame(\array_keys($this->config()->get("{$this->root}.queues")), $this->queues->ids());
        $this->assertSame(\array_keys($this->config()->get("{$this->root}.exchanges")), $this->exchanges->ids());
    }

    /**
     * @small
     *
     * @return void
     */
    public function testSetupMap(): void
    {
        $map = (array) $this->config()->get("{$this->root}.setup");

        foreach ($map as $connection_name => $settings) {
            $this->assertContains($connection_name, $this->connections->names());

            $this->assertNotEmpty($settings['queues']);

            foreach ($settings['queues'] as $queue_id) {
                $this->assertContains($queue_id, $this->queues->ids());
            }

            $this->assertNotEmpty($settings['exchanges']);

            foreach ($settings['exchanges'] as $exchanges_id) {
                $this->assertContains($exchanges_id, $this->exchanges->ids());
            }
        }
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
            $this->assertInstanceOf(\Interop\Amqp\Impl\AmqpQueue::class, $this->queues->make($name));
        }
    }

    /**
     * @return void
     */
    public function testExchangesCreating(): void
    {
        foreach (\array_keys($this->config()->get("{$this->root}.exchanges")) as $name) {
            $this->assertInstanceOf(\Interop\Amqp\Impl\AmqpTopic::class, $this->exchanges->make($name));
        }
    }

    /**
     * @small
     *
     * @return void
     */
    public function testRabbitSetupMapPassing(): void
    {
        /** @var RabbitSetupCommand $command */
        $command = $this->app->make('command.rabbit.setup');

        $this->assertSame($this->config()->get("{$this->root}.setup"), $command->getMap());
    }
}
