<?php

namespace App\Providers;

use App\Models\SubscriptionLevel;
use Illuminate\Support\Facades\View;
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
        View::composer('*', function ($view) {
            if (! isset($view->getData()['openLevels'])) {
                $view->with('openLevels', SubscriptionLevel::query()
                    ->where('is_active', true)
                    ->where('open', true)
                    ->orderByDesc('sort_order')
                    ->get(['id', 'title']));
            }
        });
    }
}
