<?php

namespace ULan\SolusVM;

use Illuminate\Support\ServiceProvider;
/**
 * Service provider for Laravel.
 */
class SolusvmServiceProvider extends ServiceProvider
{
    /**
     * Boot the provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig();
    }

    /**
     * Setup the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__.'/../config/solusvm.php');
        $this->publishes([$source => config_path('solusvm.php')]);
        $this->mergeConfigFrom($source, 'solusvm');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['solusvm'] = $this->app->share(function ($app)
        {
            return new Solusvm(
                config('solusvm.id'),
                config('solusvm.key'),
                config('solusvm.host'),
                config('solusvm.port'),
                config('solusvm.format')
            );
        });
    }
}