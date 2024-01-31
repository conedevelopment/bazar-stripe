# Bazar Stripe Payment Gateway

## Installation

```sh
composer require conedevelopment/bazar-stripe
```

## Configuration

### `.env`

```ini
STRIPE_API_KEY=
STRIPE_SECRET=
```

### Bazar Config

```php
    // config/bazar.php

    'gateway' => [
        'drivers' => [
            // ...
            'stripe' => [
                'test_mode' => env('STRIPE_TEST_MODE', false),
                'api_key' => env('STRIPE_API_KEY'),
                'secret' => env('STRIPE_SECRET'),
                'success_url' => env('STRIPE_SUCCESS_URL', '/'),
                'failure_url' => env('STRIPE_FAILURE_URL', '/'),
            ],
        ],
    ],

    // ...
```

## Webhook Events

```sh
php artisan make:listener StripeWebhookHandler
```

```php
namespace App\Listeners;

use Cone\Bazar\Stripe\WebhookInvoked;
use Stripe\Event;

class StripeWebhookHandler
{
    public function handle(WebhookInvoked $event): void
    {
        // https://stripe.com/docs/api/events/types
        $callback = match ($event->event->type) {
            'payment_intent.payment_failed' => function (Event $event): void {
                // mark transaction as failed
            },
            'payment_intent.succeeded' => function (Event $event): void {
                // mark transaction as completed and order as paid
            },
            default => function (): void {
                //
            },
        };

        call_user_func_array($callback, [$event->event]);
    }
}
```

> [!TIP]
> If [Event Discovery](https://laravel.com/docs/master/events#event-discovery) is disabled, make sure the listener is bound to the `WebhookInvoked` event in your `EventServiceProvider`.
