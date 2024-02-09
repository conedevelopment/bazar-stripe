<?php

namespace Cone\Bazar\Stripe\Listeners;

use Cone\Bazar\Models\Order;
use Cone\Bazar\Models\Transaction;
use Cone\Bazar\Stripe\Events\StripeWebhookInvoked;
use Cone\Bazar\Support\Facades\Gateway;
use Illuminate\Support\Facades\Date;
use Stripe\Event;
use Throwable;

class HandleStripeWebhook
{
    /**
     * Handle the event.
     */
    public function handle(StripeWebhookInvoked $event): void
    {
        $order = Order::proxy()
            ->newQuery()
            ->where('uuid', $event->event->data['object']['metadata']['order'])
            ->firstOrFail();

        switch ($event->event->type) {
            case 'charge.refunded':
                $this->handleRefund($event->event, $order);
                break;
            case 'payment_intent.succeeded':
                $this->handlePayment($event->event, $order);
                break;
        }
    }

    /**
     * Handle the payment.
     */
    public function handlePayment(Event $event, Order $order): void
    {
        try {
            $transaction = Transaction::proxy()->newQuery()->where('key', $event->data['object']['id'])->firstOrFail();
        } catch (Throwable $exception) {
            $transaction = Gateway::driver('stripe')->pay(
                $order,
                $event->data['object']['amount'] / 100,
                ['key' => $event->data['object']['id']]
            );
        }

        $transaction->markAsCompleted();
    }

    /**
     * Handle the refund.
     */
    public function handleRefund(Event $event, Order $order): void
    {
        foreach ($event->data['refunds']['data'] as $refund) {
            if (is_null($order->transactions->firstWhere('key', $refund['id']))) {
                Gateway::driver('stripe')->refund(
                    $order,
                    $refund['amount'] / 100,
                    ['key' => $refund['id'], 'completed_at' => Date::now()]
                );
            }
        }
    }
}
