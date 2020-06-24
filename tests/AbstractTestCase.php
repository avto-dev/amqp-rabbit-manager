<?php

namespace AvtoDev\AmqpRabbitManager\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;
use AvtoDev\AmqpRabbitManager\ServiceProvider;
use AvtoDev\AmqpRabbitManager\QueuesFactoryInterface;
use Illuminate\Config\Repository as ConfigRepository;
use AvtoDev\AmqpRabbitManager\ExchangesFactoryInterface;
use AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class AbstractTestCase extends BaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ServiceProvider::class); // @todo: remove this line?
    }

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__ . '/../vendor/laravel/laravel/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $app->register(ServiceProvider::class);

        return $app;
    }

    /**
     * Delete all queues and exchanges for all connections.
     *
     * @return void
     */
    protected function unsetBroker(): void
    {
        /** @var ConnectionsFactoryInterface $connections */
        $connections = $this->app->make(ConnectionsFactoryInterface::class);
        /** @var QueuesFactoryInterface $queues */
        $queues = $this->app->make(QueuesFactoryInterface::class);
        /** @var QueuesFactoryInterface $queues */
        $exchanges = $this->app->make(ExchangesFactoryInterface::class);

        // Delete all queues for all connections
        foreach ($connections->names() as $connection_name) {
            $connection = $connections->make($connection_name);

            foreach ($queues->ids() as $id) {
                $queue = $queues->make($id);

                $connection->deleteQueue($queue);
            }

            foreach ($exchanges->ids() as $id) {
                $exchange = $exchanges->make($id);

                $connection->deleteTopic($exchange);
            }
        }
    }

    /**
     * Get app config repository.
     *
     * @return ConfigRepository
     */
    protected function config(): ConfigRepository
    {
        return $this->app->make(ConfigRepository::class);
    }
}
