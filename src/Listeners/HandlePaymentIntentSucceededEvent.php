<?php

namespace Cone\Bazar\Stripe\Listeners;

use Cone\Bazar\Models\Order;
use Cone\Bazar\Models\Transaction;
use Cone\Bazar\Stripe\Events\StripeWebhookInvoked;
use Cone\Bazar\Support\Facades\Gateway;
use Stripe\Event;
use Throwable;

class HandlePaymentIntentSucceededEvent
{
    /**
     * Handle the event.
     */
    public function handle(StripeWebhookInvoked $event): void
    {
        switch ($event->event->type) {
            case 'payment_intent.succeeded':
                $this->completeTransaction($event->event);
                break;
        }
    }

    /**
     * Handle payment.
     */
    public function completeTransaction(Event $event): void
    {
        try {
            $transaction = Transaction::proxy()->newQuery()->where('key', $event->data['object']['id'])->firstOrFail();
        } catch (Throwable $exception) {
            $order = Order::proxy()->newQuery()->where('uuid', $event->data['object']['metadata']['order'])->firstOrFail();

            $transaction = Gateway::driver('stripe')->pay(
                $order,
                $event->data['object']['amount'] / 100,
                ['key' => $event->data['object']['id']]
            );
        }

        $transaction->markAsCompleted();
    }
}
