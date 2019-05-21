<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Commands;

use Illuminate\Console\Command;
use Interop\Amqp\AmqpQueue as Queue;
use Interop\Amqp\AmqpTopic as Exchange;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Events\Dispatcher;
use Enqueue\AmqpExt\AmqpContext as Connection;
use Symfony\Component\Console\Input\InputOption;
use AvtoDev\AmqpRabbitManager\QueuesFactoryInterface;
use AvtoDev\AmqpRabbitManager\ExchangesFactoryInterface;
use AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface;

class RabbitSetupCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'rabbit:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create queues and exchanges on RabbitMQ broker(s)';

    /**
     * Connections and queue + exchange IDs map.
     *
     * Format:
     *
     * ```
     * %connection_name_1% => [
     *   'queues'    => [ %queue_id_1%, %queue_id_2% ],
     *   'exchanges' => [ %exchange_id_1%, %exchange_id_2% ],
     * ]
     * ```
     *
     * @var array
     */
    protected $map = [];

    /**
     * @var Dispatcher
     */
    protected $events;

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
     *
     * @param Dispatcher                  $events
     * @param ConnectionsFactoryInterface $connections
     * @param QueuesFactoryInterface      $queues
     * @param ExchangesFactoryInterface   $exchanges
     * @param array                       $map
     */
    public function __construct(Dispatcher $events,
                                ConnectionsFactoryInterface $connections,
                                QueuesFactoryInterface $queues,
                                ExchangesFactoryInterface $exchanges,
                                array $map)
    {
        parent::__construct();

        $this->events      = $events;
        $this->connections = $connections;
        $this->queues      = $queues;
        $this->exchanges   = $exchanges;
        $this->map         = $map;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $recreate          = false;
        $queue_ids_only    = $this->getQueueIds();
        $exchange_ids_only = $this->getExchangeIds();

        if (((bool) $this->option('recreate')) === true) {
            if (! $this->confirmToProceed('Queues data will be lost! Are you sure?', true)) {
                return 0;
            }

            $recreate = true;
        }

        foreach ($this->map as $connection_name => $settings) {
            $this->info("RabbitMQ connection name: <fg=yellow>{$connection_name}</>");
            $connection = $this->connections->make((string) $connection_name);

            if (\is_array($settings['queues'] ?? null)) {
                $this->processQueues(
                    $connection, $settings['queues'], $queue_ids_only, $recreate
                );
            }

            if (\is_array($settings['exchanges'] ?? null)) {
                $this->processExchanges(
                    $connection, $settings['exchanges'], $exchange_ids_only, $recreate
                );
            }
        }

        return 0;
    }

    /**
     * Make passed queues using connection.
     *
     * @param Connection $connection RabbitMq connection instance
     * @param array      $queue_ids  Array of queue IDs
     * @param array|null $only       Work only with passed queue IDs (may be skipped)
     * @param bool       $recreate   It says - we should delete queue first
     *
     * @return void
     */
    protected function processQueues(Connection $connection,
                                     array $queue_ids,
                                     ?array $only = null,
                                     bool $recreate = false): void
    {
        foreach ($queue_ids as $queue_id) {
            if (\is_array($only) && ! \in_array($queue_id, $only, true)) {
                $this->line("→ Skip queue ID [{$queue_id}]");

                continue;
            }

            $queue = $this->queues->make($queue_id);
            $name  = $queue->getQueueName();

            if ($recreate === true) {
                $this->warn("✖ Delete queue with ID [{$queue_id}]: <options=bold>{$name}</>");
                $this->deleteQueue($connection, $queue);
            }

            $this->comment(\sprintf(
                "✓ Create queue with ID [{$queue_id}]: <options=bold>{$name}</> <fg=white>[%s]</>",
                \http_build_query($queue->getArguments(), '', ', ')
            ));

            $this->createQueue($connection, $queue);
        }
    }

    /**
     * Create queue on broker.
     *
     * @param Connection $connection
     * @param Queue      $queue
     *
     * @return void
     */
    protected function createQueue(Connection $connection, Queue $queue): void
    {
        $this->events->dispatch(new Events\QueueCreating($connection, $queue));

        $connection->declareQueue($queue);

        $this->events->dispatch(new Events\QueueCreated($connection, $queue));
    }

    /**
     * Delete queue from broker.
     *
     * @param Connection $connection
     * @param Queue      $queue
     *
     * @return void
     */
    protected function deleteQueue(Connection $connection, Queue $queue): void
    {
        $this->events->dispatch(new Events\QueueDeleting($connection, $queue));

        $connection->deleteQueue($queue);

        $this->events->dispatch(new Events\QueueDeleted($connection, $queue));
    }

    /**
     * Make passed exchanges using connection.
     *
     * @param Connection $connection   RabbitMq connection instance
     * @param array      $exchange_ids Array of exchange IDs
     * @param array|null $only         Work only with passed exchange IDs (may be skipped)
     * @param bool       $recreate     It says - we should delete exchange first
     *
     * @return void
     */
    protected function processExchanges(Connection $connection,
                                        array $exchange_ids,
                                        ?array $only = null,
                                        bool $recreate = false): void
    {
        foreach ($exchange_ids as $exchange_id) {
            if (\is_array($only) && ! \in_array($exchange_id, $only, true)) {
                $this->line("→ Skip exchange ID [{$exchange_id}]");

                continue;
            }

            $exchange = $this->exchanges->make($exchange_id);
            $name     = $exchange->getTopicName();

            if ($recreate === true) {
                $this->warn("✖ Delete exchange with ID [{$exchange_id}]: <options=bold>{$name}</>");

                $this->deleteExchange($connection, $exchange);
            }

            $this->comment(\sprintf(
                "✓ Create exchange with ID [{$exchange_id}]: <options=bold>{$name}</> <fg=white>[%s]</>",
                \http_build_query($exchange->getArguments(), '', ', ')
            ));

            $this->createExchange($connection, $exchange);
        }
    }

    /**
     * Create exchange on broker.
     *
     * @param Connection $connection
     * @param Exchange   $exchange
     *
     * @return void
     */
    protected function createExchange(Connection $connection, Exchange $exchange): void
    {
        $this->events->dispatch(new Events\ExchangeCreating($connection, $exchange));

        $connection->declareTopic($exchange);

        $this->events->dispatch(new Events\ExchangeCreated($connection, $exchange));
    }

    /**
     * Delete exchange from broker.
     *
     * @param Connection $connection
     * @param Exchange   $exchange
     *
     * @return void
     */
    protected function deleteExchange(Connection $connection, Exchange $exchange): void
    {
        $this->events->dispatch(new Events\ExchangeDeleting($connection, $exchange));

        $connection->deleteTopic($exchange);

        $this->events->dispatch(new Events\ExchangeDeleted($connection, $exchange));
    }

    /**
     * Get queue IDs.
     *
     * @return array|null
     */
    protected function getQueueIds(): ?array
    {
        if ($this->hasOption($option_name = 'queue-id')) {
            $ids = $this->option($option_name);

            if (\is_array($ids) && ! empty($ids)) {
                return \array_values($ids);
            }
        }

        return null;
    }

    /**
     * Get exchange IDs.
     *
     * @return array|null
     */
    protected function getExchangeIds(): ?array
    {
        if ($this->hasOption($option_name = 'exchange-id')) {
            $ids = $this->option($option_name);

            if (\is_array($ids) && ! empty($ids)) {
                return \array_values($ids);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function specifyParameters(): void
    {
        parent::specifyParameters();

        $this->addOption(
            'recreate',
            'r',
            InputOption::VALUE_NONE,
            'Force recreate queues <options=bold>(QUEUES AND EXCHANGES DATA WILL BE LOST)</>'
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force the operation'
        );

        $this->addOption(
            'queue-id',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Queue ID for a work. If defined - any another queues will be ignored'
        );

        $this->addOption(
            'exchange-id',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Exchange ID for a work. If defined - any another exchanges will be ignored'
        );
    }
}
