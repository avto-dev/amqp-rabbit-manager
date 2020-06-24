<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager;

use Closure;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpTools\ConnectionConfig;
use Enqueue\AmqpExt\AmqpConnectionFactory;
use AvtoDev\AmqpRabbitManager\Exceptions\FactoryException;

/**
 * @see \AvtoDev\AmqpRabbitManager\ServiceProvider::registerConnectionsFactory()
 */
class ConnectionsFactory implements ConnectionsFactoryInterface
{
    /**
     * Connection factories array, where key is connection name, and value - is factory instance.
     *
     * @var array<Closure>
     */
    protected $connection_factories = [];

    /**
     * @var array<Closure>
     */
    protected $context_factories = [];

    /**
     * Default connection name.
     *
     * @var string|null
     */
    protected $default_name;

    /**
     * @var array<string, mixed>
     */
    protected $connection_defaults;

    /**
     * RabbitMqManager constructor.
     *
     * @param array<string, array<string, mixed>> $connections_settings Array with connection settings,
     *                                                                  eg: `['connection-name' => [ ..options.. ], ]`
     * @param array<string, mixed>                $connection_defaults  Default connection settings
     * @param string|null                         $default              Default connection name
     */
    public function __construct(array $connections_settings, array $connection_defaults = [], ?string $default = null)
    {
        $this->default_name        = $default;
        $this->connection_defaults = $connection_defaults;

        // Fill connections settings with some defaults
        foreach ($connections_settings as $name => $settings) {
            $this->addFactory((string) $name, $settings);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addFactory(string $name, array $settings = []): void
    {
        // Create connections factory
        $this->connection_factories[$name] = Closure::fromCallable(function () use ($settings): AmqpConnectionFactory {
            return new AmqpConnectionFactory(\array_replace($this->connection_defaults, $settings));
        });

        // Create context factory
        $this->context_factories[$name] = Closure::fromCallable(function () use ($name): AmqpContext {
            return $this->connection_factories[$name]()->createContext();
        });
    }

    /**
     * Remove connection factory.
     *
     * @param string $name
     *
     * @return void
     */
    public function removeFactory(string $name): void
    {
        unset($this->connection_factories[$name], $this->context_factories[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function names(): array
    {
        return \array_keys($this->connection_factories);
    }

    /**
     * Get connection configuration.
     *
     * @param string $connection_name
     *
     * @throws FactoryException When connection is not exists
     *
     * @return ConnectionConfig
     */
    public function configuration(string $connection_name): ConnectionConfig
    {
        if (! isset($this->connection_factories[$connection_name])) {
            throw FactoryException::connectionNotExists($connection_name);
        }

        return $this->connection_factories[$connection_name]()->getConfig();
    }

    /**
     * {@inheritdoc}
     *
     * @throws FactoryException
     */
    public function default(): AmqpContext
    {
        if ($this->default_name === null) {
            throw FactoryException::defaultConnectionNotSet();
        }

        return $this->make($this->default_name);
    }

    /**
     * {@inheritdoc}
     *
     * @link <https://github.com/php-enqueue/enqueue-dev/blob/master/docs/transport/amqp.md#create-context>
     *
     * @throws FactoryException
     */
    public function make(string $connection_name): AmqpContext
    {
        if (! isset($this->context_factories[$connection_name])) {
            throw FactoryException::connectionNotExists($connection_name);
        }

        return $this->context_factories[$connection_name]();
    }
}
