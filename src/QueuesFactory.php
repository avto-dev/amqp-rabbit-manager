<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager;

use Closure;
use Interop\Amqp\AmqpQueue;
use AvtoDev\AmqpRabbitManager\Exceptions\FactoryException;

/**
 * @see \AvtoDev\AmqpRabbitManager\ServiceProvider::registerQueuesFactory()
 */
class QueuesFactory implements QueuesFactoryInterface
{
    /**
     * @var array|Closure[]
     */
    protected $factories = [];

    /**
     * QueuesFactory constructor.
     *
     * @param array $queues
     */
    public function __construct(array $queues)
    {
        foreach ($queues as $queue_id => $settings) {
            $this->addFactory((string) $queue_id, $settings);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addFactory(string $queue_id, array $settings): void
    {
        $this->factories[$queue_id] = Closure::fromCallable(function () use ($queue_id, $settings): AmqpQueue {
            $name         = $settings['name'] ?? null;
            $flags        = $settings['flags'] ?? null;
            $arguments    = $settings['arguments'] ?? null;
            $consumer_tag = $settings['consumer_tag'] ?? null;

            if (! \is_string($name) || $name === '') {
                throw FactoryException::queueNameNotSet($queue_id);
            }

            $queue = new \Interop\Amqp\Impl\AmqpQueue($name);

            if (\is_int($flags)) {
                $queue->setFlags($flags);
            }

            if (\is_array($arguments)) {
                $queue->setArguments($arguments);
            }

            if (\is_string($consumer_tag)) {
                $queue->setConsumerTag($consumer_tag);
            }

            return $queue;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function removeFactory(string $queue_id): void
    {
        unset($this->factories[$queue_id]);
    }

    /**
     * {@inheritdoc}
     */
    public function ids(): array
    {
        return \array_keys($this->factories);
    }

    /**
     * {@inheritdoc}
     *
     * @throws FactoryException
     */
    public function make(string $queue_id): AmqpQueue
    {
        if (! isset($this->factories[$queue_id])) {
            throw FactoryException::queueNotExists($queue_id);
        }

        return $this->factories[$queue_id]();
    }
}
