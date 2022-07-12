# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][keepachangelog] and this project adheres to [Semantic Versioning][semver].

## Unreleased

### Added

- Maximal `symfony/console"` package versions now is `6.*`

## v2.6.0

### Added

- Support PHP `8.x`
- Support Laravel `9.x`

### Changed

- XDebug version up to `3.1.5`
- Package `alanxz/rabbitmq-c` up to `0.11`
- Package `pdezwart/php-amqp` up to `1.11`
- Package `enqueue/amqp-ext` up to `0.10`

## v2.5.0

### Changed

- Minimal required PHP version now is `7.3`
- Minimal `symfony/*` version now is `5.0`
- Minimal `phpunit/phpunit` package versions now is `9.3`
- Version of `composer` in docker container updated up to `2.2.4`
- Deprecated methods `assertRegExp`, `assertNotRegExp` to `assertMatchesRegularExpression`, `assertDoesNotMatchRegularExpression` [#11]

[#11]:https://github.com/avto-dev/amqp-rabbit-manager/issues/11

## v2.4.0

### Changed

- Composer `2.x` is supported now [#9]

[#9]:https://github.com/avto-dev/amqp-rabbit-manager/issues/9

## v2.3.0

### Changed

- Laravel `8.x` is supported now
- Minimal `illuminate/*` package versions now is `^6.0`

## v2.2.0

### Changed

- Maximal `illuminate/*` package versions now is `7.*`
- Minimal `illuminate/*` package versions now is `^5.6`
- Minimal required PHP version now is `7.2`
- Version of `rabbitmq-c` lib in docker container updated up to `0.10.0`
- Version of `php-amqp` lib in docker container updated up to `1.10.2`
- Class `FactoryException` finalized
- Minimal required `symfony/console` version now is `^4.4` _(reason: <https://github.com/symfony/symfony/issues/32750>)_
- CI completely moved from "Travis CI" to "Github Actions" _(travis builds disabled)_

### Added

- PHP 7.4 is supported now

## v2.1.0

### Changed

- Maximal `illuminate/*` packages version now is `6.*`

### Added

- GitHub actions for a tests running

## v2.0.1

### Fixed

- Fixed configuration

## v2.0.0

### Added

- Exchanges factory
- `rabbit:setup` command events _(fired when calling command **only**)_:
  - `QueueCreating`
  - `QueueCreated`
  - `ExchangeCreating`
  - `ExchangeCreated`
  - `QueueDeleting`
  - `QueueDeleted`
  - `ExchangeDeleting`
  - `ExchangeDeleted`

### Changed

- `setup` section in configuration file and "map" in `rabbit:setup` command
- `rabbit:setup` command now creates exchanges (and supports `--exchange-id` argument)

### Fixed

- Exception messages

## v1.1.0

### Added

- Up minimal version of required package `queue-interop/queue-interop` from `^0.7` up to `^0.8`

## v1.0.0

### Added

- Connections factory
- Queues factory
- Laravel service-provider
- Command `rabbit:setup`

[keepachangelog]:https://keepachangelog.com/en/1.0.0/
[semver]:https://semver.org/spec/v2.0.0.html
