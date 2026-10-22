<?php

namespace App\Providers;

use App\Models\User;
use App\Services\CurrentBranch;
use App\Support\CoinText;
use Illuminate\Support\Facades\Blade;
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

        // @coins(text) prints the text with every "BrainCoin" shown as the animated coin.
        Blade::directive('coins', fn (string $expression) => '<?php echo '.CoinText::class."::render({$expression}); ?>");
    }
}
