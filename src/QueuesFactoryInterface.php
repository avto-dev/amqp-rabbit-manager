<?php

namespace AvtoDev\AmqpRabbitManager;

use AvtoDev\AmqpRabbitManager\Exceptions\FactoryException;
use Interop\Amqp\AmqpQueue;

/**
 * @see \AvtoDev\AmqpRabbitManager\QueuesFactory
 */
interface QueuesFactoryInterface
{
    /**
     * Get all available queue IDs.
     *
     * @return array|string[]
     */
    public function ids(): array;

    /**
     * Add queue factory.
     *
     * @param string $queue_id
     * @param array  $settings
     *
     * @return void
     */
    public function addFactory(string $queue_id, array $settings): void;

    /**
     * Remove queue factory.
     *
     * @param string $queue_id
     */
    public function removeFactory(string $queue_id): void;

    /**
     * Make queue instance by queue id.
     *
     * @param string $queue_id
     *
     * @throws FactoryException If unknown queue passed
     *
     * @return AmqpQueue
     */
    public function make(string $queue_id): AmqpQueue;
}
