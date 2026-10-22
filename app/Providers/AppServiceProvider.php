<?php

namespace App\Providers;

use App\Models\User;
use App\Services\CurrentBranch;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentBranch::class);
    }

    public function boot(): void
    {
        Gate::define('manage-center', fn (User $user) => $user->isSuperAdmin());
    }
}
