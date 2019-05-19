<?php

namespace AvtoDev\AmqpRabbitManager;

use Enqueue\AmqpExt\AmqpContext;

/**
 * @see \AvtoDev\AmqpRabbitManager\ConnectionsFactory
 */
interface ConnectionsFactoryInterface
{
    /**
     * Get all available connection names.
     *
     * @return array|string[]
     */
    public function names(): array;

    /**
     * Add connection factory.
     *
     * IMPORTANT: Passed settings should follows current used RabbitMQ client settings format!
     *
     * @param string $name
     * @param array  $settings
     *
     * @return void
     */
    public function addFactory(string $name, array $settings = []): void;

    /**
     * Remove connection factory.
     *
     * @param string $name
     *
     * @return void
     */
    public function removeFactory(string $name): void;

    /**
     * Make connection context.
     *
     * @param string $connection_name Connection name
     *
     * @return AmqpContext
     */
    public function make(string $connection_name): AmqpContext;

    /**
     * Get default connection context.
     *
     * @return AmqpContext
     */
    public function default(): AmqpContext;
}
