<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Un administrateur actif passe toutes les autorisations.
        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin() && $user->isActive()) {
                return true;
            }

            return null;
        });
    }
}
