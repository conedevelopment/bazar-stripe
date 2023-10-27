<?php

namespace Cone\Bazar\Stripe;

use Cone\Bazar\Gateway\Driver;
use Cone\Bazar\Gateway\Response;
use Cone\Bazar\Interfaces\LineItem;
use Cone\Bazar\Models\Order;
use Cone\Bazar\Models\Transaction;
use Illuminate\Http\Request;
use Stripe\StripeClient;

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
     * Process the payment.
     */
    public function pay(Order $order, float $amount = null): Transaction
    {
        return $order->pay($amount, 'stripe', []);
    }

    /**
     * Process the refund.
     */
    public function refund(Order $order, float $amount = null): Transaction
    {
        return $order->refund($amount, 'stripe', []);
    }


    /**
     * {@inheritdoc}
     */
    public function checkout(Request $request, Order $order): Response
    {
        $session = $this->client->checkout->sessions->create([
            'line_items' => $order->lineItems->map(static function (LineItem $item) use ($order): array {
                return [
                    'price_data' => [
                        'currency' => $order->getCurrency(),
                        'product_data' => [
                            'name' => $item->getName(),
                        ],
                        'unit_amount' => $item->getPrice() * 100,
                    ],
                    'quantity' => $item->getQuantity(),
                ];
            }),
            'mode' => 'payment',
            'success_url' => $this->config['success_url'],
            'cancel_url' => $this->config['cancel_url'],
        ]);

        return parent::checkout($request, $order)->url($session->url);
    }
}
