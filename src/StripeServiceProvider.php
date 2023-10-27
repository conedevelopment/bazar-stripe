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
        Gateway::extend('stripe', static function (Application $app): StripeDriver {
            return new StripeDriver($app['config']->get('bazar_stripe'));
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
