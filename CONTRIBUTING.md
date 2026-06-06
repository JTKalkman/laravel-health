# Contributing

Thank you for your interest in contributing to `jtkalkman/laravel-health`.

## Before opening a pull request

This project is in its early stages. Before spending time on a pull request, please open an issue first to discuss what you would like to change. This avoids wasted effort if the proposed change doesn't fit the direction of the project.

Unsolicited pull requests, especially AI-generated ones, will be closed without review.

## What is welcome

- Bug reports with a clear description and steps to reproduce
- Verified fixes for reported bugs
- Improvements to documentation

## What is out of scope for 1.x

- A GUI or web interface
- Reporting or notifications, use a monitoring tool like [Semonto](https://semonto.com) for that
- Queue or scheduled job monitoring, implement these as custom checks in your own application
- Support for Laravel versions below 12 or PHP versions below 8.3

## Setting up locally

```bash
git clone https://github.com/JTKalkman/laravel-health.git
cd laravel-health
composer install
```

Run the test suite:

```bash
composer test
```

## Guidelines

- Follow the existing code style, each check extends `HealthCheck`, uses `performHealthCheck()`, validates configuration with early returns, and has a corresponding test
- New built-in checks must work without `exec()` where possible, with a documented fallback when not
- All code must be tested, untested code will not be merged
- One concern per pull request

## License

By contributing you agree that your contributions will be licensed under the [MIT License](LICENSE).