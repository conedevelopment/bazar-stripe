<?php

namespace Cone\Bazar\Stripe\Listeners;

use Cone\Bazar\Models\Transaction;
use Cone\Bazar\Stripe\Events\WebhookInvoked;
use Stripe\Event;

class HandleWebhook
{
    /**
     * Handle the event.
     */
    public function handle(WebhookInvoked $event): void
    {
        $callback = match ($event->event->type) {
            'payment_intent.succeeded' => function (Event $stripeEvent): void {
                $transaction = Transaction::query()->where('key', $stripeEvent->data['object']['id'])->firstOrFail();

                $transaction->markAsCompleted();
            },
            default => function (): void {
                //
            },
        };

        call_user_func_array($callback, [$event->event]);
    }
}
