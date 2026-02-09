<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot()
    {
        parent::boot();

        // Horizon pristup samo za admin korisnike ili local environment
        Horizon::auth(function ($request) {
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
    }

    protected function gate()
    {
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
