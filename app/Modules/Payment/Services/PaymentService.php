<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Enums\PaymentGatewayType;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentIntent;
use App\Models\User;
use App\Modules\Payment\Factory\PaymentProviderFactory;
use App\Services\Payment\Contracts\PaymentProviderInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentService
{
    public function getProvider(string $gateway): PaymentProviderInterface
    {
        return PaymentProviderFactory::create($gateway);
    }

    public function attachPaymentIntent(string $trackingNumber): ?PaymentIntent
    {
        return PaymentIntent::where('tracking_number', $trackingNumber)
            ->orWhere('order_id', $trackingNumber)
            ->first();
    }

    /**
     * @param  mixed  $settings
     */
    public function processPaymentIntent(Request $request, $settings): object
    {
        /** @var array<string, mixed> $data */
        $data = $request->all();
        /** @var string $orderTrackingNumber */
        $orderTrackingNumber = $data['tracking_number'];
        /** @var string $requestedGateway */
        $requestedGateway = $data['payment_gateway'];

        $order = $this->fetchOrderByTrackingNumber($orderTrackingNumber);
        /** @var string $initialGateway */
        $initialGateway = $order->payment_gateway;

        if ($requestedGateway !== $initialGateway) {
            $chosenGateway = ucfirst(strtolower($requestedGateway));
        } else {
            $chosenGateway = $this->getActiveGatewayFromSettings($settings, $requestedGateway);
        }

        if (empty($chosenGateway)) {
            $chosenGateway = ucfirst(strtolower($requestedGateway));
        }

        $exists = $this->paymentIntentExists($orderTrackingNumber, (string) $chosenGateway);
        if (! $exists) {
            $newIntent = $this->savePaymentIntent($order, (string) $chosenGateway, $request);
            if ($data['recall_gateway'] ?? false) {
                $this->deleteOlderPaymentIntent($orderTrackingNumber, ucfirst(strtolower($initialGateway)));
                $this->updateOrderPaymentGateway($order, $initialGateway, (string) $chosenGateway);
            }

            return $newIntent;
        }

        return PaymentIntent::where(function ($q) use ($orderTrackingNumber) {
            $q->where('tracking_number', $orderTrackingNumber)->orWhere('order_id', $orderTrackingNumber);
        })->where('payment_gateway', $chosenGateway)->firstOrFail();
    }

    /**
     * @param  mixed  $settings
     */
    protected function getActiveGatewayFromSettings($settings, string $requestedGateway): ?string
    {
        $options = null;
        if (is_object($settings) && property_exists($settings, 'options')) {
            /** @var array<string, mixed> $options */
            $options = $settings->options;
        }

        if (is_array($options) && isset($options['paymentGateway']) && is_array($options['paymentGateway'])) {
            foreach ($options['paymentGateway'] as $gw) {
                if (is_array($gw) && isset($gw['name'])) {
                    if (is_string($gw['name'])) {
                        if (strtoupper($gw['name']) === strtoupper($requestedGateway)) {
                            return ucfirst(strtolower($gw['name']));
                        }
                    }
                }
            }
        }

        return null;
    }

    public function paymentIntentExists(string $trackingNumber, string $gateway): bool
    {
        return PaymentIntent::where(function ($q) use ($trackingNumber) {
            $q->where('tracking_number', $trackingNumber)->orWhere('order_id', $trackingNumber);
        })->where('payment_gateway', $gateway)->exists();
    }

    public function deleteOlderPaymentIntent(string $trackingNumber, string $gateway): void
    {
        PaymentIntent::where(function ($q) use ($trackingNumber) {
            $q->where('tracking_number', $trackingNumber)->orWhere('order_id', $trackingNumber);
        })->where('payment_gateway', $gateway)->forceDelete();
    }

    public function updateOrderPaymentGateway(Order $order, string $oldGateway, string $newGateway): void
    {
        $order->setAttribute('altered_payment_gateway', $oldGateway);
        $order->setAttribute('payment_gateway', strtoupper($newGateway));
        $order->save();

        foreach ($order->children as $child) {
            /** @var Order $child */
            $child->setAttribute('payment_gateway', strtoupper($newGateway));
            $child->setAttribute('altered_payment_gateway', $oldGateway);
            $child->save();
        }
    }

    public function savePaymentIntent(Order $order, string $gateway, Request $request): PaymentIntent
    {
        $intentInfo = $this->createPaymentIntent($order, $request, $gateway);

        return PaymentIntent::create([
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
            'payment_gateway' => $gateway,
            'payment_intent_info' => $intentInfo,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function createPaymentIntent(Order $order, Request $request, string $gateway): array
    {
        $provider = $this->getProvider($gateway);

        /** @var Model|null $wallet */
        $wallet = $order->getAttribute('wallet');
        /** @var numeric|null $walletAmount */
        $walletAmount = $wallet ? $wallet->getAttribute('amount') : 0;

        $data = [
            'amount' => ((float) $order->paid_total) - (int) ($walletAmount ?? 0),
            'order_tracking_number' => is_scalar($order->tracking_number) ? (string) $order->tracking_number : '',
            'currency' => is_string(config('shop.default_currency')) ? config('shop.default_currency') : 'usd',
        ];

        /** @var User|null $customer */
        $customer = $order->customer;

        if ($request->user() && $customer) {
            $data['user_email'] = $customer->email;
            $data['name'] = $customer->name;
        }

        if (strtoupper($gateway) === PaymentGatewayType::STRIPE->value && $request->user()) {
            $paymentCustomer = $this->createPaymentCustomer($request, $gateway);
            $data['customer'] = $paymentCustomer['customer_id'];
        }

        if (strtoupper($gateway) === PaymentGatewayType::IYZICO->value) {
            $data['ip'] = $request->ip();
        }

        return $provider->createPayment($data);
    }

    public function fetchOrderByTrackingNumber(string $trackingNumber): Order
    {
        $order = Order::where('id', $trackingNumber)->orWhere('tracking_number', $trackingNumber)->first();
        if (! $order) {
            /** @var string $notice */
            $notice = config('notice.NOT_FOUND', 'Not found');
            throw new HttpException(404, $notice);
        }

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    public function createPaymentCustomer(Request $request, string $gateway): array
    {
        $gateway = strtoupper($gateway);
        $authUser = $request->user();

        if (! $authUser) {
            throw new \RuntimeException('User not authenticated');
        }

        /** @var User $user */
        $user = $authUser;

        $existing = PaymentGateway::where('user_id', $user->id)->where('gateway_name', $gateway)->first();
        if ($existing) {
            return ['customer_id' => $existing->customer_id];
        }

        $provider = $this->getProvider($gateway);
        $customer = $provider->createCustomer([
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ]);

        PaymentGateway::create([
            'user_id' => $user->id,
            'customer_id' => $customer['customer_id'],
            'gateway_name' => $gateway,
        ]);

        return $customer;
    }

    public function handleWebhook(string $gateway, Request $request): void
    {
        $provider = $this->getProvider($gateway);
        $provider->handleWebhook($request);
    }
}
