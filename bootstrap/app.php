<?php

use App\Exceptions\GlobalExceptionHandler;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Railway's router all traffic arrives via reverse proxy: trust
        // it so HTTPS detection, secure cookies and forceScheme work.
        $middleware->trustProxies(at: '*');

        // Angular SPA talks to /api/* from the same origin: run the web
        // session + CSRF stack for stateful SPA domains so cookie-session
        // auth and flashed messages keep working for JSON requests.
        $middleware->api(prepend: \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class);
        $middleware->statefulApi();

        // Reusable in api routes: role access, e.g. ->middleware('role:admin').
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Angular SPA: turn redirect+flash responses of reused Blade
        // controllers into JSON envelopes for /api/* mutations.
        $middleware->api(append: \App\Http\Middleware\JsonFlashBridge::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        GlobalExceptionHandler::register($exceptions);
    })->create();
