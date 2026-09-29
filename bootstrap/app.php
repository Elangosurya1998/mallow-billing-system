<?php

use App\Http\Middleware\IdentifyTenant;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => IdentifyTenant::class,
        ]);
    })
    ->booted(function (): void {
        RateLimiter::for('api', function (Request $request) {
            $apiKey = $request->header('X-API-Key')
                ?? $request->bearerToken()
                ?? $request->header('X-Tenant-ID')
                ?? $request->ip();

            return Limit::perMinute(120)->by($apiKey);
        });

        RateLimiter::for('meter-ingestion', function (Request $request) {
            $apiKey = $request->header('X-API-Key')
                ?? $request->header('X-Tenant-ID')
                ?? $request->ip();

            return Limit::perMinute(10000)->by($apiKey);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
