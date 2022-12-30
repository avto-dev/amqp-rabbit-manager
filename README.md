<p align="center">
  <img src="https://laravel.com/assets/img/components/logo-laravel.svg" alt="Laravel" width="240" />
</p>

# RabbitMQ manager for Laravel applications

[![Version][badge_packagist_version]][link_packagist]
[![PHP Version][badge_php_version]][link_packagist]
[![Build Status][badge_build_status]][link_build_status]
[![Coverage][badge_coverage]][link_coverage]
[![Downloads count][badge_downloads_count]][link_packagist]
[![License][badge_license]][link_license]

This package can be used for easy access to the RabbitMQ entities like connections or queues.

> Installed php extension `ext-amqp` is required. Installation steps can be found in [Dockerfile](./docker/app/Dockerfile).

## Install

Require this package with composer using the following command:

```shell
$ composer require avto-dev/amqp-rabbit-manager "^2.0"
```

> Installed `composer` is required ([how to install composer][getcomposer]).

> You need to fix the major version of package.

After that you should "publish" package configuration file using next command:

```bash
$ php ./artisan vendor:publish --provider='AvtoDev\AmqpRabbitManager\ServiceProvider'
```

And configure it in the file `./config/rabbitmq.php`.

## Usage

At first you should execute command `rabbit:setup` for creating all queues and exchanges on RabbitMQ server.

Then, in any part of your application you can resolve connection or queue/exchange factories. For example, in artisan command:

```php
<?php

namespace App\Console\Commands;

use AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface;

class SomeCommand extends \Illuminate\Console\Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'some:command';

    /**
     * Execute the console command.
     *
     * @param ConnectionsFactoryInterface $connections
     *
     * @return void
     */
    public function handle(ConnectionsFactoryInterface $connections): void
    {
        $connections->default(); // Get the default RabbitMQ connection instance
    }
}
```

### Create queue manually

Declare queue operation creates a queue on a broker side _(use command `rabbit:setup` instead this)_:

```php
<?php

/** @var \AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface $connections */
/** @var \AvtoDev\AmqpRabbitManager\QueuesFactoryInterface $queues */

$exchange = $connections
    ->default()
    ->declareQueue($queues->make('some-queue-id'));
```

### Create exchange manually

Declare exchange operation creates a topic on a broker side _(use command `rabbit:setup` instead this)_:

```php
<?php

/** @var \AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface $connections */
/** @var \AvtoDev\AmqpRabbitManager\ExchangesFactoryInterface $exchanges */

$exchange = $connections
    ->default()
    ->declareTopic($exchanges->make('some-exchange-id'));
```

### Bind queue to exchange

Connects a queue to the exchange. So messages from that topic comes to the queue and could be processed  _(use command `rabbit:setup` **events** `\AvtoDev\AmqpRabbitManager\Commands\Events\*` instead this)_:

```php
<?php

/** @var \AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface $connections */
/** @var \AvtoDev\AmqpRabbitManager\QueuesFactoryInterface $queues */
/** @var \AvtoDev\AmqpRabbitManager\ExchangesFactoryInterface $exchanges */

$connections
    ->default()
    ->bind(new \Interop\Amqp\Impl\AmqpBind(
        $exchanges->make('some-exchange-id'),
        $queues->make('some-queue-id')
    ));
```

### Send message to exchange

Create message and them to the exchange:

```php
<?php

/** @var \AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface $connections */
/** @var \AvtoDev\AmqpRabbitManager\ExchangesFactoryInterface $exchanges */

$connection = $connections->default();
$message    = $connection->createMessage('Hello world!');

$connection
    ->createProducer()
    ->send($exchanges->make('some-exchange-id'), $message);
```

### Send message to queue

Create message and them to the queue:

```php
<?php

/** @var \AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface $connections */
/** @var \AvtoDev\AmqpRabbitManager\QueuesFactoryInterface $queues */

$connection = $connections->default();
$message    = $connection->createMessage('Hello world!');

$connection
    ->createProducer()
    ->send($queues->make('some-queue-id'), $message);
```

### Send priority message

Messages priority uses for messages ordering:

```php
<?php

/** @var \AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface $connections */
/** @var \AvtoDev\AmqpRabbitManager\QueuesFactoryInterface $queues */

$connection = $connections->default();
$message    = $connection->createMessage('Hello world!');

$connection
    ->createProducer()
    ->setPriority(10)
    // ...
    ->send($queues->make('some-queue-id'), $message);
```

### Send expiration message

Also known as message TTL:

```php
<?php

/** @var \AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface $connections */
/** @var \AvtoDev\AmqpRabbitManager\QueuesFactoryInterface $queues */

$connection = $connections->default();
$message    = $connection->createMessage('Hello world!');

$connection
    ->createProducer()
    ->setTimeToLive(60000) // 60 sec
    // ...
    ->send($queues->make('some-queue-id'), $message);
```

### Send delayed message

You should avoid to use `enqueue/amqp-tools` delay strategies, if you can. If you makes it manually - you have full control under it.

### Get (consume) single message

Get one message and continue script execution:

```php
<?php

/** @var \AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface $connections */
/** @var \AvtoDev\AmqpRabbitManager\QueuesFactoryInterface $queues */

$consumer = $connections->default()->createConsumer($queues->make('some-queue-id'));

$message = $consumer->receive();

try {
    // .. process a message ..

    $consumer->acknowledge($message);
} catch (\Exception $e) {
    // .. process exception ..

    $consumer->reject($message);
}
```

### Subscription consumer

Start (nearly) infinity loop for messages processing (you can start more then one consumer in a one time, just call ``):

```php
<?php

/** @var \AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface $connections */
/** @var \AvtoDev\AmqpRabbitManager\QueuesFactoryInterface $queues */

$connection = $connections->default();
$queue      = $queues->make('some-queue-id');
$consumer   = $connection->createConsumer($queue);
$subscriber = $connection->createSubscriptionConsumer();

$subscriber->subscribe(
    $consumer,
    function(\Interop\Amqp\AmqpMessage $message, \Enqueue\AmqpExt\AmqpConsumer $consumer): bool {
        try {
            // .. process a message ..

            $consumer->acknowledge($message);
        } catch (\Exception $e) {
            // .. process exception ..

            $consumer->reject($message);

            return false; // Subscription will be cancelled
        }

        return true; // Subscription will be continued
    }
);

$subscriber->consume(); // You can pass timeout in milliseconds
```

### Purge queue messages

Remove all messages in queue:

```php
<?php

/** @var \AvtoDev\AmqpRabbitManager\ConnectionsFactoryInterface $connections */
/** @var \AvtoDev\AmqpRabbitManager\QueuesFactoryInterface $queues */

$connection = $connections->default();

$connection->purgeQueue($queues->make('some-queue-id'));
```

### Testing

For package testing we use `phpunit` framework and `docker-ce` + `docker-compose` as develop environment. So, just write into your terminal after repository cloning:

```shell
$ make build
$ make latest # or 'make lowest'
$ make test
```

## Changes log

[![Release date][badge_release_date]][link_releases]
[![Commits since latest release][badge_commits_since_release]][link_commits]

Changes log can be [found here][link_changes_log].

## Support

[![Issues][badge_issues]][link_issues]
[![Issues][badge_pulls]][link_pulls]

If you will find any package errors, please, [make an issue][link_create_issue] in current repository.

## License

This is open-sourced software licensed under the [MIT License][link_license].

[badge_packagist_version]:https://img.shields.io/packagist/v/avto-dev/amqp-rabbit-manager.svg?maxAge=180
[badge_php_version]:https://img.shields.io/packagist/php-v/avto-dev/amqp-rabbit-manager.svg?longCache=true
[badge_build_status]:https://img.shields.io/github/actions/workflow/status/avto-dev/amqp-rabbit-manager/tests.yml
[badge_coverage]:https://img.shields.io/codecov/c/github/avto-dev/amqp-rabbit-manager/master.svg?maxAge=60
[badge_downloads_count]:https://img.shields.io/packagist/dt/avto-dev/amqp-rabbit-manager.svg?maxAge=181
[badge_license]:https://img.shields.io/packagist/l/avto-dev/amqp-rabbit-manager.svg?longCache=true
[badge_release_date]:https://img.shields.io/github/release-date/avto-dev/amqp-rabbit-manager.svg?style=flat-square&maxAge=180
[badge_commits_since_release]:https://img.shields.io/github/commits-since/avto-dev/amqp-rabbit-manager/latest.svg?style=flat-square&maxAge=180
[badge_issues]:https://img.shields.io/github/issues/avto-dev/amqp-rabbit-manager.svg?style=flat-square&maxAge=180
[badge_pulls]:https://img.shields.io/github/issues-pr/avto-dev/amqp-rabbit-manager.svg?style=flat-square&maxAge=180
[link_releases]:https://github.com/avto-dev/amqp-rabbit-manager/releases
[link_packagist]:https://packagist.org/packages/avto-dev/amqp-rabbit-manager
[link_build_status]:https://github.com/avto-dev/amqp-rabbit-manager/actions
[link_coverage]:https://codecov.io/gh/avto-dev/amqp-rabbit-manager/
[link_changes_log]:https://github.com/avto-dev/amqp-rabbit-manager/blob/master/CHANGELOG.md
[link_issues]:https://github.com/avto-dev/amqp-rabbit-manager/issues
[link_create_issue]:https://github.com/avto-dev/amqp-rabbit-manager/issues/new/choose
[link_commits]:https://github.com/avto-dev/amqp-rabbit-manager/commits
[link_pulls]:https://github.com/avto-dev/amqp-rabbit-manager/pulls
[link_license]:https://github.com/avto-dev/amqp-rabbit-manager/blob/master/LICENSE
[getcomposer]:https://getcomposer.org/download/
