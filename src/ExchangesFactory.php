<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager;

use Closure;
use Interop\Amqp\AmqpTopic;
use AvtoDev\AmqpRabbitManager\Exceptions\FactoryException;

/**
 * @see \AvtoDev\AmqpRabbitManager\ServiceProvider::registerExchangesFactory()
 */
class ExchangesFactory implements ExchangesFactoryInterface
{
    /**
     * @var array<Closure>
     */
    protected $factories = [];

    /**
     * ExchangesFactory constructor.
     *
     * @param array<string, array<string, mixed>> $exchanges
     */
    public function __construct(array $exchanges)
    {
        foreach ($exchanges as $exchange_id => $settings) {
            $this->addFactory((string) $exchange_id, $settings);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addFactory(string $exchange_id, array $settings): void
    {
        $this->factories[$exchange_id] = Closure::fromCallable(function () use ($exchange_id, $settings): AmqpTopic {
            $name      = $settings['name'] ?? null;
            $flags     = $settings['flags'] ?? null;
            $type      = $settings['type'] ?? null;
            $arguments = $settings['arguments'] ?? null;

            if (! \is_string($name) || $name === '') {
                throw FactoryException::exchangeNameNotSet($exchange_id);
            }

            $exchange = new \Interop\Amqp\Impl\AmqpTopic($name);

            if (\is_int($flags)) {
                $exchange->setFlags($flags);
            }

            if (\is_string($type)) {
                $exchange->setType($type);
            }

            if (\is_array($arguments)) {
                $exchange->setArguments($arguments);
            }

            return $exchange;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function removeFactory(string $exchange_id): void
    {
        unset($this->factories[$exchange_id]);
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
    public function make(string $exchange_id): AmqpTopic
    {
        if (! isset($this->factories[$exchange_id])) {
            throw FactoryException::exchangeNotExists($exchange_id);
        }

        return $this->factories[$exchange_id]();
    }
}
