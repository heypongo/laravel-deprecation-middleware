# Laravel Deprecation Middleware

A Laravel middleware to signal HTTP route deprecation using standardized HTTP headers ([Deprecation RFC 8594](https://www.rfc-editor.org/rfc/rfc8594), [Sunset RFC 8594](https://datatracker.ietf.org/doc/html/rfc8594), [Link RFC 8288](https://www.rfc-editor.org/rfc/rfc8288)).

## Features
- Adds `Deprecation` header to deprecated routes
- Optionally adds `Link` header with documentation URL
- Optionally adds `Sunset` header for planned removal
- Follows HTTP standards for deprecation signaling

## Requirements
- PHP 8.1 or higher
- Laravel 10 or 11

## Installation

```bash
composer require heypongo/laravel-deprecation-middleware
```

The service provider is auto-discovered by Laravel. No manual registration is required.

## Usage

Apply the middleware to your routes in `routes/web.php` or `routes/api.php`:

```php
Route::get('/old-endpoint', function () {
    // ...
})->middleware('deprecated');
```

### With Parameters
You can specify deprecation date, documentation URL, and sunset date:

```php
// Only deprecation date
Route::get('/old', fn() => ...)->middleware('deprecated:2024-06-01T00:00:00Z');

// Deprecation date and documentation URL
Route::get('/old', fn() => ...)->middleware('deprecated:2024-06-01T00:00:00Z,https://docs.example.com/deprecation');

// Deprecation date, documentation URL, and sunset date
Route::get('/old', fn() => ...)->middleware('deprecated:2024-06-01T00:00:00Z,https://docs.example.com/deprecation,2024-12-01T00:00:00Z');

// Only documentation URL and sunset date
Route::get('/old', fn() => ...)->middleware('deprecated,,https://docs.example.com/deprecation,2024-12-01T00:00:00Z');
```

#### Parameter Order
1. Deprecation date (HTTP-date or timestamp, optional)
2. Documentation URL (optional)
3. Sunset date (HTTP-date, optional)

### Example Response Headers
```
Deprecation: Wed, 01 Jun 2024 00:00:00 GMT
Link: <https://docs.example.com/deprecation>; rel="deprecation"; type="text/html"
Sunset: Sun, 01 Dec 2024 00:00:00 GMT
```

## Logging
- If the sunset date is invalid or before the deprecation date, a warning is logged using Laravel's logger.

## Testing
You are encouraged to add tests using PHPUnit and Laravel's testing tools.

## Running Tests

Install dev dependencies:

```bash
composer install
```

Run the tests:

```bash
vendor/bin/phpunit
```

## License
MIT License. See [LICENCE](LICENCE).
