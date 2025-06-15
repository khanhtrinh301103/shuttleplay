<?php
// File location: backend/app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CloudinaryService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register CloudinaryService as a singleton in the service container
        $this->app->singleton(CloudinaryService::class, function ($app) {
            return new CloudinaryService();
        });

        // You can also bind with an alias if needed
        $this->app->alias(CloudinaryService::class, 'cloudinary');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Any additional bootstrap logic can go here
        // For example, you could add some global configurations or validations
        
        // Validate Cloudinary configuration on boot
        if (config('app.env') !== 'testing') {
            $this->validateCloudinaryConfig();
        }
    }

    /**
     * Validate that required Cloudinary configuration is present
     *
     * @return void
     */
    private function validateCloudinaryConfig()
    {
        $requiredConfigs = [
            'cloudinary.cloud_name',
            'cloudinary.api_key', 
            'cloudinary.api_secret'
        ];

        foreach ($requiredConfigs as $configKey) {
            if (empty(config($configKey))) {
                \Log::warning("Cloudinary configuration missing: {$configKey}");
            }
        }
    }
}