<?php

declare(strict_types=1);

namespace App\Modules\PaymentIntent\Services;

use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\Settings;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentIntentService
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * Get or create payment intent.
     */
    public function getOrCreatePaymentIntent(Request $request, Settings $settings): object
    {
        $data = $request->all();
        $orderTrackingNumber = $request->string('tracking_number')->toString();
        $requestedGateway = $request->string('payment_gateway')->toString();
        $order = $this->fetchOrderByTrackingNumber($orderTrackingNumber);
        $initialGateway = (string) $order->payment_gateway;

        // Determine the gateway to use
        if ($requestedGateway !== $initialGateway) {
            $chosenGateway = ucfirst(strtolower($requestedGateway));
        } else {
            $chosenGateway = $this->getActiveGatewayFromSettings($settings, $requestedGateway);
        }

        if (empty($chosenGateway)) {
            // Fallback to existing gateway
            $chosenGateway = ucfirst(strtolower($requestedGateway));
        }

        $exists = $this->paymentIntentExists($orderTrackingNumber, $chosenGateway);
        if (! $exists) {
            $newIntent = $this->savePaymentIntent($order, $chosenGateway, $request);
            $recallGateway = isset($data['recall_gateway']) ? (bool) $data['recall_gateway'] : false;
            if ($recallGateway) {
                $this->deleteOlderPaymentIntent($orderTrackingNumber, ucfirst(strtolower((string) $order->payment_gateway)));
                $this->updateOrderPaymentGateway($order, $initialGateway, $chosenGateway);
            }

            return $newIntent;
        }

        return PaymentIntent::where(function ($q) use ($orderTrackingNumber) {
            $q->where('tracking_number', $orderTrackingNumber)
                ->orWhere('order_id', $orderTrackingNumber);
        })->where('payment_gateway', $chosenGateway)->firstOrFail();
    }

    /**
     * Get active gateway from settings.
     */
    protected function getActiveGatewayFromSettings(Settings $settings, string $requestedGateway): ?string
    {
        if (isset($settings->options['paymentGateway'])) {
            foreach ($settings->options['paymentGateway'] as $gw) {
                if (strtoupper((string) ($gw['name'] ?? '')) === strtoupper($requestedGateway)) {
                    return ucfirst(strtolower((string) ($gw['name'] ?? '')));
                }
            }
        }

        return null;
    }

    /**
     * Check if payment intent exists.
     */
    public function paymentIntentExists(string $trackingNumber, string $gateway): bool
    {
        return PaymentIntent::where(function ($q) use ($trackingNumber) {
            $q->where('tracking_number', $trackingNumber)
                ->orWhere('order_id', $trackingNumber);
        })->where('payment_gateway', $gateway)->exists();
    }

    /**
     * Delete older payment intent.
     */
    public function deleteOlderPaymentIntent(string $trackingNumber, string $gateway): void
    {
        PaymentIntent::where(function ($q) use ($trackingNumber) {
            $q->where('tracking_number', $trackingNumber)
                ->orWhere('order_id', $trackingNumber);
        })->where('payment_gateway', $gateway)->forceDelete();
    }

    /**
     * Update order payment gateway.
     */
    public function updateOrderPaymentGateway(Order $order, string $oldGateway, string $newGateway): void
    {
        $order->altered_payment_gateway = $oldGateway;
        $order->payment_gateway = strtoupper($newGateway);
        $order->save();

        foreach ($order->children as $child) {
            $child->payment_gateway = strtoupper($newGateway);
            $child->altered_payment_gateway = $oldGateway;
            $child->save();
        }
    }

    /**
     * Save new payment intent.
     */
    public function savePaymentIntent(Order $order, string $gateway, Request $request): PaymentIntent
    {
        $intentInfo = $this->paymentService->createPaymentIntent($order, $request, $gateway);

        return PaymentIntent::create([
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
            'payment_gateway' => $gateway,
            'payment_intent_info' => $intentInfo,
        ]);
    }

    /**
     * Fetch order by tracking number or ID.
     */
    protected function fetchOrderByTrackingNumber(string $trackingNumber): Order
    {
        $order = Order::where('id', $trackingNumber)
            ->orWhere('tracking_number', $trackingNumber)
            ->first();

        if (! $order) {
            $msg = config('notice.NOT_FOUND');
            throw new HttpException(404, is_string($msg) ? $msg : 'Not Found');
        }

        return $order;
    }
}
