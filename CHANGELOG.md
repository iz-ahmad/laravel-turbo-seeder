# Changelog

All notable changes to `laravel-turbo-seeder` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),

## [Unreleased]

### Added
- Initial release of Laravel Turbo Seeder
- Fluent API builder for easy configuration
- Two seeding strategies: Default (bulk insert) and CSV (file-based import)
- Support for MySQL, PostgreSQL, and SQLite databases
- Memory-efficient chunking and garbage collection
- Progress tracking with real-time metrics
- Comprehensive CLI commands:
  - `turbo-seeder:run` - Run seeders
  - `turbo-seeder:benchmark` - Performance benchmarking
  - `turbo-seeder:test-connection` - Test database connections
  - `turbo-seeder:clear-cache` - Clear temporary files
- `UsesTurboSeeder` trait for easy integration in seeders
- Comprehensive test suite with 120+ tests using Pest PHP
- Full PHPDoc documentation
- Example seeder demonstrating various use cases

### Features
- **Performance**: Seed 1M records in 2-3 minutes
- **Memory Efficient**: Uses less than 256MB peak memory
- **Database Optimizations**: 
  - MySQL: Multi-row INSERT, disabled constraints, autocommit optimization
  - PostgreSQL: UNNEST optimization, deferred constraints
  - SQLite: PRAGMA optimizations for maximum speed
- **CSV Strategy**: 
  - MySQL: LOAD DATA INFILE
  - PostgreSQL: COPY command
  - SQLite: Chunked CSV reading
- **Fluent API**: Chainable methods for intuitive configuration
- **Progress Tracking**: Real-time progress bars with metrics
- **Error Handling**: Comprehensive error messages and validation
- **Type Safety**: Full PHP 8.1+ type hints and strict types

### Technical Details
- Built with SOLID principles
- Action pattern for single responsibilities
- Strategy pattern for database-specific implementations
- Builder pattern for fluent API
- Dependency injection throughout
- Readonly DTOs for immutability
- PHP 8.1+ Enums for type safety
- Laravel 10/11/12 compatible

## [1.0.0] - 2024-01-xx to be released

### Added
- Initial stable release
