<?php

namespace Cone\Bazar\Stripe\Listeners;

use Cone\Bazar\Models\Order;
use Cone\Bazar\Models\Transaction;
use Cone\Bazar\Stripe\Events\StripeWebhookInvoked;
use Cone\Bazar\Support\Facades\Gateway;
use Throwable;

class HandlePaymentIntentSuccededEvent
{
    /**
     * Handle the event.
     */
    public function handle(StripeWebhookInvoked $event): void
    {
        $stripeEvent = $event->event;

        try {
            $transaction = Transaction::query()->where('key', $stripeEvent->data['object']['id'])->firstOrFail();
        } catch (Throwable $exception) {
            $order = Order::query()->where('uuid', $stripeEvent->data['object']['metadata']['order'])->firstOrFail();

            $transaction = Gateway::driver('stripe')->pay(
                $order,
                $stripeEvent->data['object']['amount'] / 100,
                ['key' => $stripeEvent->data['object']['id']]
            );
        }

        $transaction->markAsCompleted();
    }
}
