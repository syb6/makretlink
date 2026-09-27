<?php

namespace App\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;

/**
 * Central error presentation: every failure a user can trigger renders a
 * themed, friendly page (errors/minimal.blade.php) instead of a raw stack
 * trace. Debug mode (local) still shows the full laravel error page.
 */
class GlobalExceptionHandler
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (\Throwable $e, Request $request) {
            // API/AJAX callers get JSON, never an HTML page.
            if ($request->expectsJson() || $request->ajax()) {
                return null; // let Laravel's normal JSON handling proceed
            }

            $status = match (true) {
                $e instanceof \Illuminate\Http\Exceptions\HttpResponseException => 500,
                method_exists($e, 'getStatusCode') => $e->getStatusCode(),
                $e instanceof \Illuminate\Auth\AuthenticationException => 401,
                $e instanceof \Illuminate\Auth\Access\AuthorizationException => 403,
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException,
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => 404,
                $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            // Local debug: keep Laravel's detailed error screen for developers.
            if (config('app.debug')) {
                return null;
            }

            // Everything unexpected becomes a friendly themed 500.
            if ($status >= 500) {
                report($e);

                return response()->view('errors.minimal', [
                    'exception' => new \Symfony\Component\HttpKernel\Exception\HttpException(500),
                ], 500);
            }

            return null;
        });
    }
}
