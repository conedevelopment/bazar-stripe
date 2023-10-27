<?php

namespace Cone\Bazar\Stripe;

use Cone\Bazar\Stripe\StripeDriver;
use Cone\Bazar\Support\Facades\Gateway;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class StripeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! $this->app->configurationIsCached()) {
            $this->mergeConfigFrom(__DIR__.'/../config/bazar_stripe.php', 'bazar_stripe');
        }

        Gateway::extend('stripe', static function (Application $app): StripeDriver {
            return new StripeDriver($app['config']->get('bazar_stripe'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/bazar_stripe.php' => $this->app->configPath('bazar_stripe.php'),
            ], 'bazar-stripe-config');
        }
    }
}
