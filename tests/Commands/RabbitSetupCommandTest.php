<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Tests\Commands;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use AvtoDev\AmqpRabbitManager\ServiceProvider;
use AvtoDev\AmqpRabbitManager\QueuesFactoryInterface;
use AvtoDev\AmqpRabbitManager\Commands\RabbitSetupCommand;
use AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface;

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
        /** @var array $map */
        $map = $this->getObjectAttribute($this->command, 'map');

        $this->assertNotEmpty($this->setup_map);
        $this->assertSame($this->setup_map, $map);
    }

    /**
     * @small
     *
     * @return void
     */
    public function testCommandCallWithoutArguments(): void
    {
        $this->assertSame(0, $this->artisan($this->command_signature));
        $output = $this->console()->output();

        foreach ($this->setup_map as $connection_name => $queue_ids) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertRegExp("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($queue_ids as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertRegExp("~^.*Declare queue.+{$queue_id_safe}.*$~ium", $output);
                $this->assertNotRegExp("~^.*Delete queue.+{$queue_id_safe}.*$~ium", $output);
            }
        }
    }

    /**
     * @small
     *
     * @return void
     */
    public function testCommandCallWithRecreateButWithoutForce(): void
    {
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
     */
    public function testCommandCallWithRecreateAndForce(): void
    {
        $this->assertSame(0, $this->artisan($this->command_signature, [
            '--recreate' => true,
            '--force'    => true,
        ]));
        $output = $this->console()->output();

        $this->assertNotRegExp('~data.+lost~i', $output); // Alert banner should not shown

        foreach ($this->setup_map as $connection_name => $queue_ids) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertRegExp("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($queue_ids as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertRegExp("~^.*Delete queue.+{$queue_id_safe}.*$~ium", $output);
                $this->assertRegExp("~^.*Declare queue.+{$queue_id_safe}.*$~ium", $output);
            }
        }
    }

    /**
     * @small
     *
     * @return void
     */
    public function testPassingUnknownQueueIds(): void
    {
        $this->assertSame(0, $this->artisan($this->command_signature, [
            '--queue-id' => [$random_queue_id = Str::random()],
        ]));
        $output = $this->console()->output();

        foreach ($this->setup_map as $connection_name => $queue_ids) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertRegExp("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($queue_ids as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertRegExp("~^.*Skip.+{$queue_id_safe}.*$~ium", $output);
            }
        }

        $this->assertNotRegExp('~' . \preg_quote($random_queue_id, '/') . '~', $output);
    }

    /**
     * @small
     *
     * @return void
     */
    public function testPassingAllKnownQueueIds(): void
    {
        $queue_ids = Arr::flatten($this->setup_map);

        $this->assertSame(0, $this->artisan($this->command_signature, [
            '--queue-id' => $queue_ids,
        ]));
        $output = $this->console()->output();

        foreach ($this->setup_map as $connection_name => $queue_ids) {
            $connection_name_safe = \preg_quote($connection_name, '/');
            $this->assertRegExp("~^.+connection.*{$connection_name_safe}.*$~ium", $output);

            foreach ($queue_ids as $queue_id) {
                $queue_id_safe = \preg_quote($queue_id, '/');
                $this->assertRegExp("~^.*Declare queue.+{$queue_id_safe}.*$~ium", $output);
                $this->assertNotRegExp("~^.*Skip.+{$queue_id_safe}.*$~ium", $output);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteAllQueues();

        $this->command   = $this->app->make(RabbitSetupCommand::class);
        $this->setup_map = $this->config()->get(ServiceProvider::getConfigRootKeyName() . '.setup');
    }
}
