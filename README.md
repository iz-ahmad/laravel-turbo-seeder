# A package for laravel to enable High-performance bulk seeding

Blazing fast database seeder for Laravel - seed millions of records in minutes, or even seconds!

## Installation

You can install the package via composer:

```bash
composer require iz-ahmad/laravel-turbo-seeder
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-turbo-seeder-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-turbo-seeder-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-turbo-seeder-views"
```

## Usage

```php
$turboSeeder = new IzAhmad\TurboSeeder();
echo $turboSeeder->echoPhrase('Hello, IzAhmad!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [iz-ahmad](https://github.com/iz-ahmad)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
