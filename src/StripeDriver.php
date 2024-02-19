<?php

namespace Cone\Bazar\Stripe;

use Cone\Bazar\Exceptions\TransactionDriverMismatchException;
use Cone\Bazar\Gateway\Driver;
use Cone\Bazar\Gateway\Response;
use Cone\Bazar\Interfaces\LineItem;
use Cone\Bazar\Models\Order;
use Cone\Bazar\Models\Transaction;
use Cone\Bazar\Stripe\Events\StripeWebhookInvoked;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeDriver extends Driver
{
    /**
     * The driver name.
     */
    protected string $name = 'stripe';

    /**
     * The Stripe client instance.
     */
    public readonly StripeClient $client;

    /**
     * The Stripe session instance.
     */
    protected ?Session $session = null;

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
    public function getTransactionUrl(Transaction $transaction): ?string
    {
        return sprintf('https://dashboard.stripe.com/%spayments/%s', $this->config['test_mode'] ? 'test/' : '', $transaction->key);
    }

    /**
     * {@inheritdoc}
     */
    public function getCaptureUrl(Order $order): string
    {
        return URL::route('bazar.gateway.capture', [
            'driver' => $this->name,
        ]).'?session_id={CHECKOUT_SESSION_ID}';
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
            'mode' => 'payment',
            'success_url' => $this->getCaptureUrl($order),
            'cancel_url' => $this->getFailureUrl($order),
            'payment_intent_data' => [
                'metadata' => [
                    'order' => $order->uuid,
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function checkout(Request $request, Order $order): Order
    {
        $this->session = $this->createSession($order);

        return parent::checkout($request, $order);
    }

    /**
     * {@inheritdoc}
     */
    public function handleCheckout(Request $request, Order $order): Response
    {
        $response = parent::handleCheckout($request, $order);

        if (! is_null($this->session)) {
            $response->url($this->session->url);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveOrderForCapture(Request $request): Order
    {
        $this->session = $this->client->checkout->sessions->retrieve(
            $request->input('session_id')
        );

        return Order::proxy()->newQuery()->where('bazar_orders.uuid', $this->session->client_reference_id)->firstOrFail();
    }

    /**
     * {@inheritdoc}
     */
    public function capture(Request $request, Order $order): Order
    {
        if (! $order->transactions()->where('bazar_transactions.key', $this->session->payment_intent)->exists()) {
            $this->pay($order, null, ['key' => $this->session->payment_intent]);
        }

        return $order;
    }

    /**
     * {@inheritdoc}
     */
    public function handleNotification(Request $request): Response
    {
        $event = Webhook::constructEvent(
            $request->getContent(),
            $request->server('HTTP_STRIPE_SIGNATURE'),
            $this->config['secret']
        );

        StripeWebhookInvoked::dispatch($event);

        return parent::handleNotification($request);
    }

    /**
     * Handle the manual transaction creation.
     */
    public function handleManualTransaction(Transaction $transaction): void
    {
        match ($transaction->type) {
            Transaction::PAYMENT => $this->handleManualPayment($transaction),
            Transaction::REFUND => $this->handleManualRefund($transaction),
            default => null,
        };
    }

    /**
     * Handle the manual payment creatiion.
     */
    public function handleManualPayment(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the manual refund creatiion.
     */
    public function handleManualRefund(Transaction $transaction): void
    {
        $payment = $transaction->order->transaction;

        if ($payment->driver !== $transaction->driver) {
            throw new TransactionDriverMismatchException(sprintf(
                "The refund driver [%s] does not match the base transaction's driver [%s].",
                $transaction->driver,
                $payment->driver
            ));
        }

        $refund = $this->client->refunds->create([
            'payment_intent' => $payment->key,
            'amount' => $transaction->amount * 100,
            'metadata' => [
                'order' => $transaction->order->uuid,
            ],
        ]);

        $transaction->setAttribute('key', $refund->id)->save();
    }
}
