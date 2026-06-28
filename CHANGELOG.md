# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-06-28

### Changed
- `HealthCheckResult::status` is now typed as `HealthCheckStatus` enum instead of `string` for improved type safety

## [1.0.3] - 2026-06-28

### Fixed
- Missing `HealthCheckStatus::from()` cast in `HealthController` causing `TypeError` when resolving worst status

## [1.0.2] - 2026-06-28

### Added
- `php artisan health:check` command, runs all checks and displays colored results in the terminal with exit codes for use in deployment pipelines
- `php artisan health:clear` command, clears the cached health check results

## [1.0.1] - 2026-06-28

### Fixed
- Replace closures with serializable class tuples in `config/health.php` to fix `php artisan config:cache` incompatibility in production

## [1.0.0] - 2026-06-28

### Added
- Initial release
- Built-in checks: `DiskSpaceCheck`, `DiskSpaceInodeCheck`, `MemoryCheck`, `CpuLoadCheck`, `DatabaseConnectionCheck`, `DatabaseConnectionCountCheck`
- Semonto-compatible JSON output with `ok`, `warning`, `critic`, `error` status values
- Secure-by-default authentication via configurable header and secret key
- Result caching with cache unavailability fallback
- HTTPS enforcement middleware
- Custom check support via extending the `HealthCheck` abstract class
- `Formatter` helper for consistent human-readable values
- `HealthCheckStatus` enum with `priority()` and `worst()` methods
