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
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('signed');
    }

    /**
     * Handle the incoming webhook request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $order = Order::query()->findOrFail($request->input('order'));

        $status = $request->input('status');

        return Redirect::to('/')->with('status', $status);
    }
}
