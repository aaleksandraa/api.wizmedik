<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class HorizonServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Only register Horizon if the package is installed
        if (class_exists(\Laravel\Horizon\HorizonApplicationServiceProvider::class)) {
            $this->app->register(\Laravel\Horizon\HorizonApplicationServiceProvider::class);
        }
    }

    public function boot()
    {
        // Only configure Horizon if it's available
        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            \Laravel\Horizon\Horizon::auth(function ($request) {
                // Za production - samo admin korisnici
                if (app()->environment('production')) {
                    return auth()->check() &&
                           auth()->user() &&
                           (auth()->user()->role === 'admin' ||
                            (method_exists(auth()->user(), 'isAdmin') && auth()->user()->isAdmin()));
                }

                // Za local/staging - svi
                return true;
            });

            Gate::define('viewHorizon', function ($user = null) {
                if (app()->environment('production')) {
                    return $user &&
                           ($user->role === 'admin' ||
                            (method_exists($user, 'isAdmin') && $user->isAdmin()));
                }

                return true;
            });
        }
    }
}
