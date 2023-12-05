<?php

namespace Cone\Bazar\Stripe\Http\Controllers;

use Cone\Bazar\Stripe\Events\WebhookInvoked;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Stripe\Webhook;

class WebhookController extends Controller
{
    /**
     * Handle the incoming webhook request.
     */
    public function __invoke(Request $request): Response
    {
        $event = Webhook::constructEvent(
            $request->getContent(),
            $request->server('HTTP_STRIPE_SIGNATURE'),
            Config::get('bazar_stripe.secret')
        );

        WebhookInvoked::dispatch($event);

        return Response('', Response::HTTP_NO_CONTENT);
    }
}
