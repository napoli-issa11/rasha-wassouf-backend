<?php

namespace App\Providers;

use App\Services\CloudinaryService;
use Cloudinary\Cloudinary as CloudinarySdk;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Cloudinary singleton with sanitized credentials and local SSL bypass
        $this->app->singleton(CloudinarySdk::class, function () {
            return CloudinaryService::getClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
