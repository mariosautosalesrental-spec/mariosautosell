<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Setting::SCOPED_BINDING, fn () => Setting::loadCached());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Links, assets and redirects must be https in production.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
