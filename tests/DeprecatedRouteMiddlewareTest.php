<?php

declare(strict_types=1);

namespace HeyPongo\DeprecationMiddleware\Tests;

use HeyPongo\DeprecationMiddleware\DeprecationMiddlewareServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class DeprecatedRouteMiddlewareTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [DeprecationMiddlewareServiceProvider::class];
    }

    public function test_deprecation_header_is_set()
    {
        Route::middleware('deprecated:2024-06-01T00:00:00Z')->get('/deprecated', fn () => 'ok');
        $response = $this->get('/deprecated');
        $response->assertHeader('Deprecation', 'Sat, 01 Jun 2024 00:00:00 GMT');
    }

    public function test_link_and_sunset_headers_are_set()
    {
        Route::middleware('deprecated:2024-06-01T00:00:00Z,https://docs.example.com/deprecation,2024-12-01T00:00:00Z')
            ->get('/deprecated2', fn () => 'ok');
        $response = $this->get('/deprecated2');
        $response->assertHeader('Deprecation', 'Sat, 01 Jun 2024 00:00:00 GMT');
        $response->assertHeader('Link', '<https://docs.example.com/deprecation>; rel="deprecation"; type="text/html"');
        $response->assertHeader('Sunset', 'Sun, 01 Dec 2024 00:00:00 GMT');
    }

    public function test_sunset_header_not_set_if_invalid()
    {
        Log::shouldReceive('warning')->once()->withArgs(function ($message) {
            return str_contains($message, 'Invalid Sunset date format');
        });
        Route::middleware('deprecated:2024-06-01T00:00:00Z,https://docs.example.com/deprecation,invalid-date')
            ->get('/deprecated3', fn () => 'ok');
        $response = $this->get('/deprecated3');
        $response->assertHeader('Deprecation', 'Sat, 01 Jun 2024 00:00:00 GMT');
        $response->assertHeaderMissing('Sunset');
    }

    public function test_sunset_header_not_set_if_before_deprecation()
    {
        Log::shouldReceive('warning')->once()->withArgs(function ($message) {
            return str_contains($message, 'Sunset date (2024-01-01T00:00:00Z) is before Deprecation date');
        });
        Route::middleware('deprecated:2024-06-01T00:00:00Z,https://docs.example.com/deprecation,2024-01-01T00:00:00Z')
            ->get('/deprecated4', fn () => 'ok');
        $response = $this->get('/deprecated4');
        $response->assertHeader('Deprecation', 'Sat, 01 Jun 2024 00:00:00 GMT');
        $response->assertHeaderMissing('Sunset');
    }
}
