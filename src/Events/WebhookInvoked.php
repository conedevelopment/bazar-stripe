<?php

namespace Cone\Bazar\Stripe\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Stripe\Event;

class WebhookInvoked
{
    use Dispatchable;

    /**
     * The event instance.
     */
    public Event $event;

    /**
     * Create a new event instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }
}
