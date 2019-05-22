# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][keepachangelog] and this project adheres to [Semantic Versioning][semver].

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
