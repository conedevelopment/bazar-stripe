<?php

namespace Cone\Bazar\Stripe;

use Cone\Bazar\Gateway\Driver;
use Cone\Bazar\Gateway\Response;
use Cone\Bazar\Interfaces\LineItem;
use Cone\Bazar\Models\Order;
use Cone\Bazar\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Throwable;

class StripeDriver extends Driver
{
    /**
     * The Stripe client instance.
     */
    protected StripeClient $client;

    /**
     * Create a new driver instance.
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->client = new StripeClient($config['api_key']);
    }

    /**
     * {@inheritdoc}
     */
    public function pay(Order $order, float $amount = null): Transaction
    {
        return $order->pay($amount, 'stripe', []);
    }

    /**
     * {@inheritdoc}
     */
    public function refund(Order $order, float $amount = null): Transaction
    {
        return $order->refund($amount, 'stripe', []);
    }

    /**
     * Create a new Stripe session.
     */
    protected function createSession(Order $order): Session
    {
        return $this->client->checkout->sessions->create([
            'client_reference_id' => $order->uuid,
            'line_items' => $order->lineItems->map(static function (LineItem $item) use ($order): array {
                return [
                    'price_data' => [
                        'currency' => strtolower($order->getCurrency()),
                        'product_data' => [
                            'name' => $item->getName(),
                        ],
                        'unit_amount' => $item->getPrice() * 100,
                    ],
                    'quantity' => $item->getQuantity(),
                ];
            })->toArray(),
            'mode' => 'payment',
            'success_url' => $this->redirectUrl($order, ['status' => 'success']),
            'cancel_url' => $this->redirectUrl($order, ['status' => 'cancelled']),
        ]);
    }

    /**
     * Create a new method.
     */
    protected function redirectUrl(Order $order, array $query = []): string
    {
        return URL::signedRoute('bazar.stripe.payment', array_merge(['order' => $order->getKey()], $query));
    }

    /**
     * {@inheritdoc}
     */
    public function checkout(Request $request, Order $order): Response
    {
        try {
            $url = $this->createSession($order)->url;
        } catch (Throwable $exception) {
            $url = $this->redirectUrl($order, ['status' => 'failed']);
        }

        return parent::checkout($request, $order)->url($url);
    }
}
