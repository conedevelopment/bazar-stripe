<?php

namespace Cone\Bazar\Stripe\Http\Controllers;

use Cone\Bazar\Models\Order;
use Cone\Bazar\Support\Facades\Gateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;

class PaymentController extends Controller
{
    /**
     * Handle the incoming payment request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $status = $request->input('status');

        $session = Gateway::driver('stripe')->client->checkout->sessions->retrieve(
            $request->input('session_id')
        );

        $order = Order::query()->where('uuid', $session->client_reference_id)->firstOrFail();

        if ($status === 'success') {
            Gateway::driver('stripe')->pay($order, null, ['key' => $session->payment_intent]);
        }

        return Redirect::to('/')->with('status', $status);
    }
}
