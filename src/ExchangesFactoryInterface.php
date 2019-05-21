<?php

namespace AvtoDev\AmqpRabbitManager;

use Interop\Amqp\AmqpTopic;
use AvtoDev\AmqpRabbitManager\Exceptions\FactoryException;

/**
 * @see \AvtoDev\AmqpRabbitManager\ExchangesFactory
 */
interface ExchangesFactoryInterface
{
    /**
     * Get all available exchange IDs.
     *
     * @return array|string[]
     */
    public function ids(): array;

    /**
     * Add exchange factory.
     *
     * @param string $exchange_id
     * @param array  $settings
     *
     * @return void
     */
    public function addFactory(string $exchange_id, array $settings): void;

    /**
     * Remove exchange factory.
     *
     * @param string $exchange_id
     */
    public function removeFactory(string $exchange_id): void;

    /**
     * Make exchange instance by exchange id.
     *
     * @param string $exchange_id
     *
     * @throws FactoryException If unknown queue passed
     *
     * @return AmqpTopic
     */
    public function make(string $exchange_id): AmqpTopic;
}
