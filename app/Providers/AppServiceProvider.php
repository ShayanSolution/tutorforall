<?php

namespace App\Providers;

use App\Repositories\SearchLocationRepository;
use App\Repositories\Contracts\SearchLocationInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\Illuminate\Contracts\Http\Kernel::class);

        $this->app->bind(SearchLocationInterface::class, function ($app) {
            return new SearchLocationRepository($app->make(SearchLocationRepository::class));
        });
    }
}
