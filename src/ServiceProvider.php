<?php

declare(strict_types = 1);

namespace AvtoDev\AmqpRabbitManager;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\Container;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Register package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->initializeConfigs();

        $this->registerQueuesFactory();
        $this->registerExchangesFactory();
        $this->registerConnectionsFactory();

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    /**
     * Get config root key name.
     *
     * @return string
     */
    public static function getConfigRootKeyName(): string
    {
        return \basename(static::getConfigPath(), '.php');
    }

    /**
     * Returns path to the configuration file.
     *
     * @return string
     */
    public static function getConfigPath(): string
    {
        return __DIR__ . '/../config/rabbitmq.php';
    }

    /**
     * Initialize configs.
     *
     * @return void
     */
    protected function initializeConfigs(): void
    {
        $this->mergeConfigFrom(static::getConfigPath(), static::getConfigRootKeyName());

        $this->publishes([
            \realpath(static::getConfigPath()) => config_path(\basename(static::getConfigPath())),
        ], 'config');
    }

    /**
     * Register queues factory.
     *
     * @return void
     */
    protected function registerQueuesFactory(): void
    {
        $this->app->singleton(
            QueuesFactoryInterface::class,
            function (Container $container): QueuesFactoryInterface {
                /** @var ConfigRepository $config */
                $config = $container->make(ConfigRepository::class);

                return new QueuesFactory((array) $config->get(static::getConfigRootKeyName() . '.queues'));
            }
        );
    }

    /**
     * Register exchanges factory.
     *
     * @return void
     */
    protected function registerExchangesFactory(): void
    {
        $this->app->singleton(
            ExchangesFactoryInterface::class,
            function (Container $container): ExchangesFactoryInterface {
                /** @var ConfigRepository $config */
                $config = $container->make(ConfigRepository::class);

                return new ExchangesFactory((array) $config->get(static::getConfigRootKeyName() . '.exchanges'));
            }
        );
    }

    /**
     * Register connections factory.
     *
     * @return void
     */
    protected function registerConnectionsFactory(): void
    {
        $this->app->singleton(
            ConnectionsFactoryInterface::class,
            function (Container $container): ConnectionsFactoryInterface {
                /** @var ConfigRepository $config */
                $config = $container->make(ConfigRepository::class);
                $root   = static::getConfigRootKeyName();

                return new ConnectionsFactory(
                    (array) $config->get("{$root}.connections"),
                    (array) $config->get("{$root}.connection_defaults"),
                    $config->get("{$root}.default_connection")
                );
            }
        );
    }

    /**
     * Register console commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->app->singleton('command.rabbit.setup', function (Container $container): Command {
            /** @var ConfigRepository $config */
            $config = $container->make(ConfigRepository::class);

            return $container->make(Commands\RabbitSetupCommand::class, [
                'map' => $config->get(static::getConfigRootKeyName() . '.setup'),
            ]);
        });

        $this->commands('command.rabbit.setup');
    }
}
