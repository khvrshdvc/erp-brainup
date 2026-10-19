<?php

namespace App\Providers;

use App\Services\CurrentBranch;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentBranch::class);
    }

    public function boot(): void
    {
    }
}
