<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        Paginator::defaultView('pagination::bootstrap-5');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-5');
    }
}
