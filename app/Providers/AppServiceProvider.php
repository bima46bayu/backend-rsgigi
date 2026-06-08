<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        Gate::define('viewApiDocs', function ($user = null) {
            return app()->environment('local') || ($user && $user->hasRole('super-admin'));
        });
    }
}
