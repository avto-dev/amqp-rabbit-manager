<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Contracts\Console\Kernel;
use AvtoDev\AmqpRabbitManager\ServiceProvider;
use AvtoDev\AmqpRabbitManager\QueuesFactoryInterface;
use AvtoDev\AmqpRabbitManager\ExchangesFactoryInterface;
use AvtoDev\AmqpRabbitManager\Commands\RabbitSetupCommand;
use AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface;
use AvtoDev\AmqpRabbitManager\Commands\Events\QueueCreated;
use AvtoDev\AmqpRabbitManager\Commands\Events\QueueDeleted;
use AvtoDev\AmqpRabbitManager\Commands\Events\QueueCreating;
use AvtoDev\AmqpRabbitManager\Commands\Events\QueueDeleting;
use AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeCreated;
use AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeDeleted;
use AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeCreating;
use AvtoDev\AmqpRabbitManager\Commands\Events\ExchangeDeleting;

/**
 * @covers \AvtoDev\AmqpRabbitManager\Commands\RabbitSetupCommand<extended>
 */
class RabbitSetupCommandTest extends AbstractCommandTestCase
{
    /**
     * Indicates if the console output should be mocked.
     *
     * @var bool
     */
    public $mockConsoleOutput = false;

    /**
     * Command signature.
     *
     * @var string
     */
    protected $command_signature = 'rabbit:setup';

    /**
     * @var RabbitSetupCommand
     */
    protected $command;

    /**
     * @var array[]
     */
    protected $setup_map;

    /**
     * @var ConnectionsFactoryInterface
     */
    protected $connections;

    /**
     * @var QueuesFactoryInterface
     */
    protected $queues;

    /**
     * @var ExchangesFactoryInterface
     */
    protected $exchanges;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connections = $this->app->make(ConnectionsFactoryInterface::class);
        $this->queues      = $this->app->make(QueuesFactoryInterface::class);
        $this->exchanges   = $this->app->make(ExchangesFactoryInterface::class);

        $this->unsetBroker();

        $this->setup_map = $this->config()->get(ServiceProvider::getConfigRootKeyName() . '.setup');

        $this->command = $this->app->make(RabbitSetupCommand::class, [
            'map' => $this->setup_map,
        ]);
    }

    /**
     * @return void
     */
    public function testCommandExecution(): void
    {
        $this->assertSame(0, $this->artisan($this->command_signature));
    }

    /**
     * @small
     *
     * @return void
     */
    public function testMapInitialization(): void
    {
        $this->assertNotEmpty($this->setup_map);
        $this->assertSame($this->setup_map, $this->command->getMap());
    }

    /**
     * @small
     *
     * @return void
     * @throws Exception
     *
     */
    public function testCommandCallWithoutArguments(): void
    {
        $this->expectsEvents([
            QueueCreating::class, QueueCreated::class, ExchangeCreating::class, ExchangeCreated::class,
        ]);

        $this->doesntExpectEvents([
            QueueDeleting::class, QueueDeleted::class, ExchangeDeleting::class, ExchangeDeleted::class,
        ]);

        $this->assertSame(0, $this->artisan($this->command_signature));
        $output = $this->console()->output();

        foreach ($this->setup_map as $connection_name => $settings) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertRegExp("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($settings['queues'] as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertRegExp("~^.*Create queue.+{$queue_id_safe}.*$~ium", $output);
                $this->assertNotRegExp("~^.*Delete queue.+{$queue_id_safe}.*$~ium", $output);
            }

            foreach ($settings['exchanges'] as $exchange_id) {
                $exchange_id_safe = \preg_quote($exchange_id, '/');
                $this->assertRegExp("~^.*Create exchange.+{$exchange_id_safe}.*$~ium", $output);
                $this->assertNotRegExp("~^.*Delete exchange.+{$exchange_id_safe}.*$~ium", $output);
            }
        }
    }

    /**
     * @small
     *
     * @return void
     *
     * @throws Exception
     */
    public function testCommandCallWithEmptyMap(): void
    {
        $this->doesntExpectEvents([
            QueueCreating::class, QueueCreated::class, ExchangeCreating::class, ExchangeCreated::class,
            QueueDeleting::class, QueueDeleted::class, ExchangeDeleting::class, ExchangeDeleted::class,
        ]);

        $this->command = $this->app->make(RabbitSetupCommand::class, [
            'map' => [],
        ]);

        $this->app->make(Kernel::class)->registerCommand($this->command);

        $this->assertSame(0, $this->artisan($this->command_signature));
        $output = $this->console()->output();

        $this->assertNotRegExp('~^.+connection.*$~ium', $output);
        $this->assertNotRegExp('~^.*Create queue.*$~ium', $output);
        $this->assertNotRegExp('~^.*Delete queue.*$~ium', $output);
        $this->assertNotRegExp('~^.*Create exchange.*$~ium', $output);
        $this->assertNotRegExp('~^.*Delete exchange.*$~ium', $output);
    }

    /**
     * @small
     *
     * @return void
     */
    public function testCommandCallWithRecreateButWithoutForce(): void
    {
        $this->doesntExpectEvents([
            QueueCreating::class, QueueCreated::class, ExchangeCreating::class, ExchangeCreated::class,
            QueueDeleting::class, QueueDeleted::class, ExchangeDeleting::class, ExchangeDeleted::class,
        ]);

        $this->assertSame(0, $this->artisan($this->command_signature, [
            '--recreate' => true,
            //'--force'  => true,
        ]));
        $output = $this->console()->output();

        $this->assertRegExp('~data.+lost~i', $output);
        $this->assertRegExp('~Command.+cancel~i', $output);
    }

    /**
     * @small
     *
     * @return void
     * @throws Exception
     *
     */
    public function testCommandCallWithRecreateAndForce(): void
    {
        $this->expectsEvents([
            QueueCreating::class, QueueCreated::class, ExchangeCreating::class, ExchangeCreated::class,
            QueueDeleting::class, QueueDeleted::class, ExchangeDeleting::class, ExchangeDeleted::class,
        ]);

        $this->assertSame(0, $this->artisan($this->command_signature, [
            '--recreate' => true,
            '--force'    => true,
        ]));
        $output = $this->console()->output();

        $this->assertNotRegExp('~data.+lost~i', $output); // Alert banner should not shown

        foreach ($this->setup_map as $connection_name => $settings) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertRegExp("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($settings['queues'] as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertRegExp("~^.*Create queue.+{$queue_id_safe}.*$~ium", $output);
                $this->assertRegExp("~^.*Delete queue.+{$queue_id_safe}.*$~ium", $output);
            }

            foreach ($settings['exchanges'] as $exchange_id) {
                $exchange_id_safe = \preg_quote($exchange_id, '/');
                $this->assertRegExp("~^.*Create exchange.+{$exchange_id_safe}.*$~ium", $output);
                $this->assertRegExp("~^.*Delete exchange.+{$exchange_id_safe}.*$~ium", $output);
            }
        }
    }

    /**
     * @small
     *
     * @return void
     * @throws Exception
     *
     */
    public function testPassingUnknownQueueIds(): void
    {
        $this->doesntExpectEvents([
            QueueCreating::class, QueueCreated::class, ExchangeCreating::class, ExchangeCreated::class,
            QueueDeleting::class, QueueDeleted::class, ExchangeDeleting::class, ExchangeDeleted::class,
        ]);

        $this->assertSame(0, $this->artisan($this->command_signature, [
            '--queue-id'    => [$random_queue_id = Str::random()],
            '--exchange-id' => [$random_exchange_id = Str::random()],
        ]));
        $output = $this->console()->output();

        foreach ($this->setup_map as $connection_name => $settings) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertRegExp("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($settings['queues'] as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertRegExp("~^.*Skip.+{$queue_id_safe}.*$~ium", $output);
            }

            foreach ($settings['exchanges'] as $exchange_id) {
                $exchange_id_safe = \preg_quote($exchange_id, '/');
                $this->assertRegExp("~^.*Skip.+{$exchange_id_safe}.*$~ium", $output);
            }
        }

        $this->assertNotRegExp('~' . \preg_quote($random_queue_id, '/') . '~', $output);
        $this->assertNotRegExp('~' . \preg_quote($random_exchange_id, '/') . '~', $output);
    }

    /**
     * @small
     *
     * @return void
     * @throws Exception
     *
     */
    public function testPassingAllKnownQueueAndExchangeIds(): void
    {
        $this->expectsEvents([
            QueueCreating::class, QueueCreated::class, ExchangeCreating::class, ExchangeCreated::class,
        ]);

        $this->doesntExpectEvents([
            QueueDeleting::class, QueueDeleted::class, ExchangeDeleting::class, ExchangeDeleted::class,
        ]);

        $queue_ids = $exchange_ids = [];

        foreach ($this->setup_map as $connection_name => $settings) {
            foreach ($settings['queues'] as $queue_id) {
                $queue_ids[] = $queue_id;
            }

            foreach ($settings['exchanges'] as $exchange_id) {
                $exchange_ids[] = $exchange_id;
            }
        }

        $this->assertSame(0, $this->artisan($this->command_signature, [
            '--queue-id'    => $queue_ids,
            '--exchange-id' => $exchange_ids,
        ]));
        $output = $this->console()->output();

        foreach ($this->setup_map as $connection_name => $settings) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertRegExp("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($settings['queues'] as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertRegExp("~^.*Create queue.+{$queue_id_safe}.*$~ium", $output);
                $this->assertNotRegExp("~^.*Skip.+{$queue_id_safe}.*$~ium", $output);
            }

            foreach ($settings['exchanges'] as $exchange_id) {
                $exchange_id_safe = \preg_quote($exchange_id, '/');
                $this->assertRegExp("~^.*Create exchange.+{$exchange_id_safe}.*$~ium", $output);
                $this->assertNotRegExp("~^.*Skip.+{$exchange_id_safe}.*$~ium", $output);
            }
        }
    }
}
