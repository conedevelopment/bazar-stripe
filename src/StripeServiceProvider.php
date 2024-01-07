<?php

namespace Cone\Bazar\Stripe;

use Cone\Bazar\Stripe\Events\WebhookInvoked;
use Cone\Bazar\Stripe\Http\Controllers\PaymentController;
use Cone\Bazar\Stripe\Http\Controllers\WebhookController;
use Cone\Bazar\Stripe\Listeners\HandleWebhook;
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
            $this->publishes(
                [__DIR__.'/../config/bazar_stripe.php' => $this->app->configPath('bazar_stripe.php')],
                'bazar-stripe-config'
            );
        }

        $this->registerRoutes();
        $this->registerEvents();
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if (! $this->app->routesAreCached()) {
            $this->app['router']
                ->get('/bazar/stripe/payment', PaymentController::class)
                ->name('bazar.stripe.payment');

            $this->app['router']
                ->post('/bazar/stripe/webhook', WebhookController::class)
                ->name('bazar.stripe.webhook');
        }
    }

    /**
     * Register events.
     */
    protected function registerEvents(): void
    {
        $this->app['events']->listen(WebhookInvoked::class, HandleWebhook::class);
    }
}
