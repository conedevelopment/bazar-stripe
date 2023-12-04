<?php

namespace Cone\Bazar\Stripe\Http\Controllers;

use Cone\Bazar\Models\Order;
use Cone\Bazar\Support\Facades\Gateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    /**
     * Handle the incoming webhook request.
     */
    public function __invoke(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $session = Gateway::driver('stripe')->client->checkout->sessions->retrieve(
            $data['session_id']
        );

        // Handle event

        return Response('', Response::HTTP_NO_CONTENT);
    }
}
