<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The Resend transport needs an API key (config/services.php ->
        // services.resend.key, i.e. RESEND_API_KEY). Without one it would
        // fatal on the first send, so fall back to the log transport and
        // make the gap impossible to miss in the logs.
        if (trim((string) env('RESEND_API_KEY')) === '') {
            $this->app->extend('mail.manager', function ($manager) {
                $manager->setDefaultDriver('log');

                return $manager;
            });

            Log::warning('RESEND_API_KEY not set — falling back to log mail transport (no real email will be sent).');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Haversine support for the test suite: PDO's bundled SQLite build
        // ships without trig functions, so register the ones the Market::nearby
        // scope uses. No effect on MySQL (production) — native functions there.
        if (config('database.default') === 'sqlite') {
            $pdo = DB::connection()->getPdo();

            $pdo->sqliteCreateFunction('radians', fn ($deg) => deg2rad((float) $deg), 1);
            $pdo->sqliteCreateFunction('cos', fn ($x) => cos((float) $x), 1);
            $pdo->sqliteCreateFunction('sin', fn ($x) => sin((float) $x), 1);
            $pdo->sqliteCreateFunction('asin', fn ($x) => asin(max(-1.0, min(1.0, (float) $x))), 1);
            $pdo->sqliteCreateFunction('sqrt', fn ($x) => sqrt(max(0.0, (float) $x)), 1);
            $pdo->sqliteCreateFunction('pow', fn ($b, $e) => pow((float) $b, (float) $e), 2);
        }
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        Paginator::defaultView('pagination::bootstrap-5');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-5');
    }
}
