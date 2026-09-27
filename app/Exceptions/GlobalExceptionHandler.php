<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Central error presentation.
 *
 * - JSON/AJAX callers always get structured JSON, never an HTML page.
 * - Browser requests get the themed errors/minimal page for every handled
 *   status (401/403/404/405/419/429/500/503) — including in local debug,
 *   so mistakes like GET /assistant look like app errors, not framework
 *   stack traces.
 * - Truly unexpected 500s are reported and shown generically (no internals
 *   leak), except in local debug where the full trace aids development.
 */
class GlobalExceptionHandler
{
    /** Exception -> HTTP status, evaluated top down. */
    private static function statusFor(Throwable $e): int
    {
        return match (true) {
            $e instanceof PostTooLargeException => 413,
            $e instanceof ThrottleRequestsException => 429,
            $e instanceof TokenMismatchException => 419,
            $e instanceof AuthenticationException => 401,
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException => 403,
            $e instanceof ModelNotFoundException => 404,
            $e instanceof NotFoundHttpException => 404,
            $e instanceof MethodNotAllowedHttpException => 405,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };
    }

    /** User-safe one-liner per status; 500 never leaks internals. */
    private static function messageFor(int $status): string
    {
        return match ($status) {
            401 => 'Please sign in to continue.',
            403 => 'You do not have permission to do that.',
            404 => 'We could not find what you were looking for.',
            405 => 'That request method is not supported here.',
            413 => 'That upload was too large.',
            419 => 'Your session expired. Refresh the page and try again.',
            429 => 'Too many requests — please wait a moment and try again.',
            503 => 'We are briefly offline for maintenance. Please try again soon.',
            default => 'Something went wrong on our side. Please try again shortly.',
        };
    }

    public static function register(Exceptions $exceptions): void
    {
        // Structured JSON for API/AJAX callers (assistant widget, fetch()).
        $exceptions->render(function (Throwable $e, Request $request) {
            // Validation: the framework already responds correctly (422 JSON
            // for API callers, redirect with session errors for web forms).
            if ($e instanceof ValidationException) {
                return null;
            }

            if (! ($request->expectsJson() || $request->ajax())) {
                return null; // browser flow handled by the next renderable
            }

            $status = self::statusFor($e);

            if ($status >= 500) {
                report($e);
            }

            return response()->json([
                'message' => self::messageFor($status),
                'error' => $status >= 500 ? 'server_error' : class_basename($e),
            ], $status);
        });

        // Themed page for browser requests on any handled status.
        $exceptions->render(function (Throwable $e, Request $request) {
            // Guests must hit the framework's login redirect (with intended
            // URL), not a themed 401 page.
            if ($e instanceof AuthenticationException) {
                return null;
            }

            // Web-form validation redirects back with errors (never a page).
            if ($e instanceof ValidationException) {
                return null;
            }

            $status = self::statusFor($e);

            // Local debug keeps Laravel's detailed screen ONLY for real 500s
            // (framework traces help development); every 4xx is still themed,
            // so route mistakes never show raw Ignition pages.
            if ($status >= 500 && config('app.debug')) {
                return null;
            }

            if ($status >= 500) {
                Log::error('Unhandled exception on '.$request->method().' '.$request->path(), [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }

            // Honor HttpException headers (Allow for 405, Retry-After for 429).
            $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

            return response()->view('errors.minimal', [
                'exception' => new HttpException($status),
                'hint' => $e instanceof MethodNotAllowedHttpException
                    ? collect(explode(',', (string) ($headers['Allow'] ?? '')))->map(fn ($m) => trim($m))->filter()->implode(', ')
                    : null,
            ], $status, $headers);
        });
    }
}
