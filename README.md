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
STRIPE_SUCCESS_URL=
STRIPE_CANCEL_URL=
```

### Customizing Redirect URL After Payment Intent

```php
namespace App\Providers;

use Cone\Bazar\Models\Order;
use Cone\Bazar\Models\Transaction;
use Cone\Bazar\Stripe\StripeDriver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        StripeDriver::redirectUrlAfterPayment(function (Order $order, string $status, Transaction $transaction = null): string {
            return match ($status) {
                'success' => '/shop/account/orders/'.$order->uuid,
                default => '/shop/retry-checkout?order='.$order->uuid;
            };
        });
    }
}
```

> [!NOTE]  
> The `redirectUrlAfterPayment` method overrides `STRIPE_SUCCESS_URL` and `STRIPE_CANCEL_URL` values for the given order.

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
        $callback = match ($event->event->type) {
            // https://stripe.com/docs/api/events/types
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

        call_user_func_array($callback, [$evet->event]);
    }
}
```

> [!TIP]
> If [Event Discovery](https://laravel.com/docs/master/events#event-discovery) is disabled, make sure the listener is bound to the `WebhookInvoked` event in your `EventServiceProvider`.
