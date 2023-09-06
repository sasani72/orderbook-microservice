<?php

namespace App\Providers;

use App\Services\OrderBookService;
use Illuminate\Support\ServiceProvider;

class OrderBookServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OrderBookService::class, function ($app) {
            return new OrderBookService(
                $app->make(OrderRepository::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
