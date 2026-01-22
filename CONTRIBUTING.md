# Contributing to Laravel Turbo Seeder

Thank you for considering contributing to Laravel Turbo Seeder! This document provides guidelines and instructions for contributing.

## Code of Conduct

This project adheres to a Code of Conduct that all contributors are expected to follow. Please be respectful and constructive in all interactions.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the issue list as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

- **Clear title and description**
- **Steps to reproduce** the issue
- **Expected behavior**
- **Actual behavior**
- **Environment details** (PHP version, Laravel version, database type)
- **Screenshots** (if applicable)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- **Clear title and description**
- **Use case** - Why is this enhancement useful?
- **Proposed solution** (if you have one)
- **Alternatives considered** (if any)

### Pull Requests

1. **Fork the repository**
2. **Create your feature branch** (`git checkout -b feature/amazing-feature`)
3. **Make your changes**
4. **Add tests** for new functionality
5. **Ensure all tests pass** (`composer test` or `vendor/bin/pest`)
6. **Ensure code style is correct** (`./vendor/bin/pint --dirty`)
7. **suggest improvements to the documentation** if needed
8. **Commit your changes** (`git commit -m 'add some amazing feature'`)
9. **Push to the branch** (`git push origin feature/amazing-feature`)
10. **Open a Pull Request** to the main branch

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Git

### Installation

1. Clone the repository:
```bash
git clone https://github.com/iz-ahmad/laravel-turbo-seeder.git
cd laravel-turbo-seeder
```

2. Install dependencies:
```bash
composer install
```

3. Run tests to ensure everything works:
```bash
composer test
```

## Coding Standards

### PHP Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code formatting. Run:

```bash
composer format
```

### Code Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use strict types always: `declare(strict_types=1);`
- Add PHPDoc comments for all public methods and classes
- Use type hints for all parameters and return types
- Follow SOLID principles
- Keep methods focused and single-purpose
- Keep classes also focused

### Testing

- All new features must include tests
- Tests should be written using Pest PHP
- Aim for high test coverage
- Tests should be fast and isolated
- Use descriptive test names

### Documentation

- Update README.md for user-facing changes
- Add PHPDoc comments for new classes and methods
- Update CHANGELOG.md for significant changes

## Project Structure

```
src/
├── Actions/          # Single-action classes
├── Builder/          # Fluent API builder
├── Commands/         # Artisan commands
├── Contracts/        # Interfaces
├── DTOs/            # Data Transfer Objects
├── Enums/           # PHP Enums
├── Examples/        # Example seeders
├── Facades/         # Laravel facades
├── Services/        # Core services
├── Strategies/      # Database strategies
└── Traits/          # Reusable traits

tests/
├── Feature/         # Feature tests
└── Unit/            # Unit tests
```

## Testing Guidelines

### Running Tests

```bash
# run all tests
composer test

# run with coverage
composer test-coverage

# Run specific test file
vendor/bin/pest tests/Feature/BasicSeedingTest.php

# Run specific test
vendor/bin/pest --filter "can seed basic records"
```

## Questions?

if you have questions, please:
- Open an issue for discussion
- Check existing issues and pull requests
- Review the codebase and documentation

Thank you for contributing to Laravel Turbo Seeder!
