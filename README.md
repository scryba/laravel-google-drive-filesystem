# scryba/laravel-google-drive-filesystem

[![Latest Version on Packagist](https://img.shields.io/packagist/v/scryba/laravel-google-drive-filesystem.svg?style=flat-square)](https://packagist.org/packages/scryba/laravel-google-drive-filesystem)
[![Total Downloads](https://img.shields.io/packagist/dt/scryba/laravel-google-drive-filesystem.svg?style=flat-square)](https://packagist.org/packages/scryba/laravel-google-drive-filesystem)
[![License](https://img.shields.io/packagist/l/scryba/laravel-google-drive-filesystem.svg?style=flat-square)](https://packagist.org/packages/scryba/laravel-google-drive-filesystem)

Google Drive filesystem adapter for Laravel 10, 11, and 12.

- **Author:** Michael K. Laweh (<contact@michael.laweitech.com>)
- **Homepage:** <https://michael.laweitech.com/>
- **Repository:** <https://github.com/scryba/laravel-google-drive-filesystem>
- **Funding:** [Buy me a coffee](https://michael.laweitech.com/buy-me-a-coffee)

## Quick Installation

Install via Composer:

```
composer require scryba/laravel-google-drive-filesystem
```

For advanced installation and VCS/development setup, see [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Quick Configuration

Publish the config file and set up your `.env`:

```
php artisan vendor:publish --tag=google-drive-config
```

Add your Google Drive credentials to your `.env` file. For a detailed step-by-step guide, see [docs/GETTING-TOKENS.md](docs/GETTING-TOKENS.md).

## Usage

After configuring, you can use the Google Drive disk in your Laravel application like this:

```php
use Illuminate\Support\Facades\Storage;

// Store a file
Storage::disk('google')->put('example.txt', 'Hello, Google Drive!');

// Retrieve a file
$content = Storage::disk('google')->get('example.txt');

// List files in a directory
$files = Storage::disk('google')->files('/');

// Delete a file
Storage::disk('google')->delete('example.txt');
```

For advanced usage, see [docs/USAGE.md](docs/USAGE.md).

## Laravel Compatibility

- Laravel 10.x, 11.x, 12.x
- PHP 8.1+

## License

MIT

## Support

For issues, bug reports, or feature requests, please visit the [GitHub Issues page](https://github.com/scryba/laravel-google-drive-filesystem/issues).
