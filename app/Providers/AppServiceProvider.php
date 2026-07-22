<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Admin is a global role that bypasses every policy (SPEC §4). Policies below only encode
        // the non-admin, workspace-scoped rules. Policies are auto-discovered (App\Policies\*Policy).
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);
    }
}
