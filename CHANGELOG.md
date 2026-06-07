# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-03-07

### Fixed
- Replace closures with serializable class tuples in config to fix `php artisan config:cache` incompatibility in production

## [1.0.0] - 2025-03-06

### Added
- Initial release
- Built-in checks: disk space, disk inodes, memory, CPU load, database connection, database connection count
- Semonto-compatible JSON output
- Configurable authentication, HTTPS requirement, caching, and route
- Custom check support via extending `HealthCheck` abstract class
