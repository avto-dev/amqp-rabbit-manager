# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][keepachangelog] and this project adheres to [Semantic Versioning][semver].

## v2.0.0

### Added

- Exchanges factory
- Events: ...

### Changed

- `setup` section in configuration file and "map" in `rabbit:setup` command
- `rabbit:setup` command now creates exchanges

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
