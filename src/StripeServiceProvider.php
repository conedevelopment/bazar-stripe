<?php

namespace Cone\Bazar\Stripe;

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
            return new StripeDriver($app['config']->get('bazar.gateway.drivers.stripe', []));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(
            Events\StripeWebhookInvoked::class, Listeners\HandleStripeWebhook::class
        );
    }
}
