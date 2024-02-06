<?php

namespace Cone\Bazar\Stripe\Actions;

use Cone\Root\Actions\Action;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class SendPaymentLink extends Action
{
    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Collection $models): void
    {
        // Gateway::driver('stripe')
    }
}
