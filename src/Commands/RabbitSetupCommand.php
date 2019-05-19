<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager\Commands;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use AvtoDev\AmqpRabbitManager\ServiceProvider;
use Symfony\Component\Console\Input\InputOption;
use AvtoDev\AmqpRabbitManager\QueuesFactoryInterface;
use AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

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
    protected $description = 'Create queues on RabbitMQ broker(s)';

    /**
     * Connections and queue IDs map.
     *
     * @var array
     */
    protected $map = [];

    /**
     * {@inheritDoc}
     *
     * @param ConfigRepository $config
     */
    public function __construct(ConfigRepository $config)
    {
        parent::__construct();

        $this->map = $config->get(ServiceProvider::getConfigRootKeyName() . '.setup');
    }

    /**
     * Execute the console command.
     *
     * @param ConnectionsFactoryInterface $connections
     * @param QueuesFactoryInterface      $queues
     *
     * @return int
     */
    public function handle(ConnectionsFactoryInterface $connections,
                           QueuesFactoryInterface $queues): int
    {
        $recreate            = false;
        $queue_ids_fo_a_work = $this->getQueueIds();

        if (((bool) $this->option('recreate')) === true) {
            if (! $this->confirmToProceed('Queues data will be lost! Are you sure?', true)) {
                return 0;
            }

            $recreate = true;
        }

        foreach ($this->map as $connection_name => $queue_ids) {
            $this->info("RabbitMQ connection name: <fg=yellow>{$connection_name}</>");
            $connection = $connections->make((string) $connection_name);

            foreach ($queue_ids as $queue_id) {
                if (\is_array($queue_ids_fo_a_work) && ! \in_array($queue_id, $queue_ids_fo_a_work, true)) {
                    $this->line("→ Skip [{$queue_id}]");

                    continue;
                }

                $queue = $queues->make($queue_id);
                $name  = $queue->getQueueName();

                if ($recreate === true) {
                    $this->warn("✖ Delete queue [{$queue_id}]: <options=bold>{$name}</>");
                    $connection->deleteQueue($queue);
                }

                // @link <https://stackoverflow.com/a/11427549/2252921>
                $arguments = \http_build_query($queue->getArguments(), '', ', ');

                $this->comment("✓ Declare queue [{$queue_id}]: <options=bold>{$name}</> <fg=white>[{$arguments}]</>");
                $connection->declareQueue($queue);

                unset($queue);
            }

            unset($connection);
        }

        return 0;
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
     * {@inheritdoc}
     */
    protected function specifyParameters(): void
    {
        parent::specifyParameters();

        $this->addOption(
            'recreate',
            'r',
            InputOption::VALUE_NONE,
            'Force recreate queues <options=bold>(QUEUES DATA WILL BE LOST)</>'
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
            'Queue ID for a work (allowed: ' . \implode(', ', Arr::flatten($this->map)) . '). If ' .
            'defined - any another queues will be ignored'
        );
    }
}
