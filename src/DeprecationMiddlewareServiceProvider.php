<?php

declare(strict_types=1);

namespace HeyPongo\DeprecationMiddleware;

use Illuminate\Support\ServiceProvider;

class DeprecationMiddlewareServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('deprecated', DeprecatedRouteMiddleware::class);
    }
}
