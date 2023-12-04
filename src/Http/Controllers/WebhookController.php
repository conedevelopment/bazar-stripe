<?php

namespace Cone\Bazar\Stripe\Http\Controllers;

use Cone\Bazar\Models\Order;
use Cone\Bazar\Support\Facades\Gateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Stripe\Event;
use Stripe\PaymentIntent;

class WebhookController extends Controller
{
    /**
     * Handle the incoming webhook request.
     */
    public function __invoke(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $event = Event::constructFrom($data);

        $session = Gateway::driver('stripe')->client->checkout->sessions->retrieve(
            $data['session_id']
        );

        $response = match ($event->type) {
            'payment_intent.succeeded' => '', // $event->data->object PaymentIntent
            default => '',
        };

        return Response($response, Response::HTTP_NO_CONTENT);
    }
}
