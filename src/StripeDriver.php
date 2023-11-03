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
    public readonly StripeClient $client;

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
    public function pay(Order $order, float $amount = null, array $attrributes = []): Transaction
    {
        return $order->pay($amount, 'stripe', $attrributes);
    }

    /**
     * {@inheritdoc}
     */
    public function refund(Order $order, float $amount = null, array $attrributes = []): Transaction
    {
        return $order->refund($amount, 'stripe', $attrributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionUrl(Transaction $transaction): ?string
    {
        return sprintf('https://dashboard.stripe.com/%spayments/%s', $this->config['test_mode'] ? 'test/' : '', $transaction->key);
    }

    /**
     * Create a new Stripe session.
     */
    protected function createSession(Order $order): Session
    {
        return $this->client->checkout->sessions->create([
            'client_reference_id' => $order->uuid,
            'customer_email' => $order->user->email,
            'line_items' => $order->items->map(static function (LineItem $item) use ($order): array {
                return [
                    'price_data' => [
                        'currency' => strtolower($order->getCurrency()),
                        'product_data' => ['name' => $item->getName()],
                        'unit_amount' => $item->getPrice() * 100,
                    ],
                    'quantity' => $item->getQuantity(),
                ];
            })->toArray(),
            'billing_address_collection' => 'required',
            'mode' => 'payment',
            'success_url' => $this->redirectUrl('success'),
            'cancel_url' => $this->redirectUrl('cancelled'),
        ]);
    }

    /**
     * Get the redirect URL.
     */
    protected function redirectUrl(string $status): string
    {
        return URL::route('bazar.stripe.payment', ['status' => $status]).'&session_id={CHECKOUT_SESSION_ID}';
    }

    /**
     * {@inheritdoc}
     */
    public function checkout(Request $request, Order $order): Response
    {
        try {
            $url = $this->createSession($order)->url;
        } catch (Throwable $exception) {
            $url = $this->redirectUrl('failed');
        }

        return parent::checkout($request, $order)->url($url);
    }
}
