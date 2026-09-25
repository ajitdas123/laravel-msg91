<div align="center">
    <h1>Laravel Msg91</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/ajit-das/laravel-msg91"><img src="https://img.shields.io/packagist/v/ajit-das/laravel-msg91.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/ajit-das/laravel-msg91"><img src="https://img.shields.io/packagist/php-v/ajit-das/laravel-msg91.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/ajit-das/laravel-msg91"><img src="https://badge.laravel.cloud/badge/ajit-das/laravel-msg91?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/ajit-das/laravel-msg91/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/ajit-das/laravel-msg91/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/ajit-das/laravel-msg91"><img src="https://img.shields.io/packagist/dt/ajit-das/laravel-msg91.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Laravel notification channels for MSG91 SMS, WhatsApp and OTP, with fluent message builders.

## Installation

You can install the package via Composer:

```bash
composer require ajit-das/laravel-msg91
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="laravel-msg91"
```

Or, you may publish each resource individually:

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="laravel-msg91-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="laravel-msg91-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="laravel-msg91-lang"
```

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="laravel-msg91-assets"
```

## Usage

<!-- Add a basic usage example here. -->

## Contributing

Thank you for considering contributing to Laravel Msg91! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Credits

- [Ajit Das](https://github.com/ajit-das)
- [All Contributors](../../contributors)

## License

Laravel Msg91 is open-sourced software licensed under the [MIT license](LICENSE.md).
