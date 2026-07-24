<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->shouldForceHttps()) {
            URL::forceScheme('https');
        }
    }

    private function shouldForceHttps(): bool
    {
        if (filter_var(config('app.force_https'), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        if ($this->app->environment('production')) {
            return true;
        }

        $appUrl = (string) config('app.url');

        return str_starts_with($appUrl, 'https://');
    }
}
