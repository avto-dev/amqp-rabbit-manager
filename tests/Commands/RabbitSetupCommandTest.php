<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
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
 *
 * @group usesExternalServices
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
     * @throws Exception
     *
     * @return void
     */
    public function testCommandCallWithoutArguments(): void
    {
        Event::fake();

        $this->assertSame(0, $this->artisan($this->command_signature));
        $output = $this->console()->output();

        foreach ($this->setup_map as $connection_name => $settings) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertMatchesRegularExpression("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($settings['queues'] as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertMatchesRegularExpression("~^.*Create queue.+{$queue_id_safe}.*$~ium", $output);
                $this->assertDoesNotMatchRegularExpression("~^.*Delete queue.+{$queue_id_safe}.*$~ium", $output);
            }

            foreach ($settings['exchanges'] as $exchange_id) {
                $exchange_id_safe = \preg_quote($exchange_id, '/');
                $this->assertMatchesRegularExpression("~^.*Create exchange.+{$exchange_id_safe}.*$~ium", $output);
                $this->assertDoesNotMatchRegularExpression("~^.*Delete exchange.+{$exchange_id_safe}.*$~ium", $output);
            }
        }

        Event::assertDispatched(QueueCreating::class);
        Event::assertDispatched(QueueCreated::class);
        Event::assertDispatched(ExchangeCreating::class);
        Event::assertDispatched(ExchangeCreated::class);

        Event::assertNotDispatched(QueueDeleting::class);
        Event::assertNotDispatched(QueueDeleted::class);
        Event::assertNotDispatched(ExchangeDeleting::class);
        Event::assertNotDispatched(ExchangeDeleted::class);
    }

    /**
     * @small
     *
     * @throws Exception
     *
     * @return void
     */
    public function testCommandCallWithEmptyMap(): void
    {
        Event::fake();

        $this->command = $this->app->make(RabbitSetupCommand::class, [
            'map' => [],
        ]);

        $this->app->make(Kernel::class)->registerCommand($this->command);

        $this->assertSame(0, $this->artisan($this->command_signature));
        $output = $this->console()->output();

        $this->assertDoesNotMatchRegularExpression('~^.+connection.*$~ium', $output);
        $this->assertDoesNotMatchRegularExpression('~^.*Create queue.*$~ium', $output);
        $this->assertDoesNotMatchRegularExpression('~^.*Delete queue.*$~ium', $output);
        $this->assertDoesNotMatchRegularExpression('~^.*Create exchange.*$~ium', $output);
        $this->assertDoesNotMatchRegularExpression('~^.*Delete exchange.*$~ium', $output);

        Event::assertNotDispatched(QueueCreating::class);
        Event::assertNotDispatched(QueueCreated::class);
        Event::assertNotDispatched(ExchangeCreating::class);
        Event::assertNotDispatched(ExchangeCreated::class);
        Event::assertNotDispatched(QueueDeleting::class);
        Event::assertNotDispatched(QueueDeleted::class);
        Event::assertNotDispatched(ExchangeDeleting::class);
        Event::assertNotDispatched(ExchangeDeleted::class);
    }

    /**
     * @small
     *
     * @group www
     *
     * @return void
     */
    public function testCommandCallWithRecreateButWithoutForce(): void
    {
        Event::fake();

        $this->assertSame(0, $this->artisan($this->command_signature, [
            '--recreate'       => true,
            '--no-interaction' => true,
        ]));
        $output = $this->console()->output();

        $this->assertMatchesRegularExpression('~data.+lost~i', $output);
        $this->assertMatchesRegularExpression('~Command.+cancel~i', $output);

        Event::assertNotDispatched(QueueCreating::class);
        Event::assertNotDispatched(QueueCreated::class);
        Event::assertNotDispatched(ExchangeCreating::class);
        Event::assertNotDispatched(ExchangeCreated::class);
        Event::assertNotDispatched(QueueDeleting::class);
        Event::assertNotDispatched(QueueDeleted::class);
        Event::assertNotDispatched(ExchangeDeleting::class);
        Event::assertNotDispatched(ExchangeDeleted::class);
    }

    /**
     * @small
     *
     * @throws Exception
     *
     * @return void
     */
    public function testCommandCallWithRecreateAndForce(): void
    {
        Event::fake();

        $this->assertSame(0, $this->artisan($this->command_signature, [
            '--recreate' => true,
            '--force'    => true,
        ]));
        $output = $this->console()->output();

        $this->assertDoesNotMatchRegularExpression('~data.+lost~i', $output); // Alert banner should not shown

        foreach ($this->setup_map as $connection_name => $settings) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertMatchesRegularExpression("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($settings['queues'] as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertMatchesRegularExpression("~^.*Create queue.+{$queue_id_safe}.*$~ium", $output);
                $this->assertMatchesRegularExpression("~^.*Delete queue.+{$queue_id_safe}.*$~ium", $output);
            }

            foreach ($settings['exchanges'] as $exchange_id) {
                $exchange_id_safe = \preg_quote($exchange_id, '/');
                $this->assertMatchesRegularExpression("~^.*Create exchange.+{$exchange_id_safe}.*$~ium", $output);
                $this->assertMatchesRegularExpression("~^.*Delete exchange.+{$exchange_id_safe}.*$~ium", $output);
            }
        }

        Event::assertDispatched(QueueCreating::class);
        Event::assertDispatched(QueueCreated::class);
        Event::assertDispatched(ExchangeCreating::class);
        Event::assertDispatched(ExchangeCreated::class);
        Event::assertDispatched(QueueDeleting::class);
        Event::assertDispatched(QueueDeleted::class);
        Event::assertDispatched(ExchangeDeleting::class);
        Event::assertDispatched(ExchangeDeleted::class);
    }

    /**
     * @small
     *
     * @throws Exception
     *
     * @return void
     */
    public function testPassingUnknownQueueIds(): void
    {
        Event::fake();

        $this->assertSame(0, $this->artisan($this->command_signature, [
            '--queue-id'    => [$random_queue_id = Str::random()],
            '--exchange-id' => [$random_exchange_id = Str::random()],
        ]));
        $output = $this->console()->output();

        foreach ($this->setup_map as $connection_name => $settings) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertMatchesRegularExpression("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($settings['queues'] as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertMatchesRegularExpression("~^.*Skip.+{$queue_id_safe}.*$~ium", $output);
            }

            foreach ($settings['exchanges'] as $exchange_id) {
                $exchange_id_safe = \preg_quote($exchange_id, '/');
                $this->assertMatchesRegularExpression("~^.*Skip.+{$exchange_id_safe}.*$~ium", $output);
            }
        }

        $this->assertDoesNotMatchRegularExpression('~' . \preg_quote($random_queue_id, '/') . '~', $output);
        $this->assertDoesNotMatchRegularExpression('~' . \preg_quote($random_exchange_id, '/') . '~', $output);

        Event::assertNotDispatched(QueueCreating::class);
        Event::assertNotDispatched(QueueCreated::class);
        Event::assertNotDispatched(ExchangeCreating::class);
        Event::assertNotDispatched(ExchangeCreated::class);
        Event::assertNotDispatched(QueueDeleting::class);
        Event::assertNotDispatched(QueueDeleted::class);
        Event::assertNotDispatched(ExchangeDeleting::class);
        Event::assertNotDispatched(ExchangeDeleted::class);
    }

    /**
     * @small
     *
     * @throws Exception
     *
     * @return void
     */
    public function testPassingAllKnownQueueAndExchangeIds(): void
    {
        Event::fake();

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
            $this->assertMatchesRegularExpression("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($settings['queues'] as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertMatchesRegularExpression("~^.*Create queue.+{$queue_id_safe}.*$~ium", $output);
                $this->assertDoesNotMatchRegularExpression("~^.*Skip.+{$queue_id_safe}.*$~ium", $output);
            }

            foreach ($settings['exchanges'] as $exchange_id) {
                $exchange_id_safe = \preg_quote($exchange_id, '/');
                $this->assertMatchesRegularExpression("~^.*Create exchange.+{$exchange_id_safe}.*$~ium", $output);
                $this->assertDoesNotMatchRegularExpression("~^.*Skip.+{$exchange_id_safe}.*$~ium", $output);
            }
        }

        Event::assertDispatched(QueueCreating::class);
        Event::assertDispatched(QueueCreated::class);
        Event::assertDispatched(ExchangeCreating::class);
        Event::assertDispatched(ExchangeCreated::class);

        Event::assertNotDispatched(QueueDeleting::class);
        Event::assertNotDispatched(QueueDeleted::class);
        Event::assertNotDispatched(ExchangeDeleting::class);
        Event::assertNotDispatched(ExchangeDeleted::class);
    }
}
