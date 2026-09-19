<?php

declare(strict_types=1);

namespace App\Modules\Payment\Actions;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Modules\Payment\Contracts\PaymentGatewayFactoryInterface;
use App\Modules\Payment\Services\PaymentMethodPersistService;
use App\Modules\PaymentMethod\DTO\PaymentMethodData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

final class StorePaymentMethodAction
{
    public function __construct(
        private readonly PaymentGatewayFactoryInterface $gatewayFactory,
        private readonly PaymentMethodPersistService $persistService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  User  $user
     */
    public function execute(array $data, Authenticatable $user): PaymentMethod
    {
        /** @var User $user */
        $paymentMethodData = PaymentMethodData::fromRequest($data);

        $provider = $this->gatewayFactory->create($paymentMethodData->payment_gateway);

        return DB::transaction(function () use ($paymentMethodData, $user, $provider) {
            $paymentMethod = $provider->retrievePaymentMethod(
                $paymentMethodData->method_key,
                $paymentMethodData->method_type
            );

            $attachedMethod = $this->attachToCustomerIfSupported(
                $provider,
                $paymentMethodData->method_key,
                $user,
                $paymentMethodData->method_type
            );

            /** @var object $methodData */
            $methodData = $attachedMethod ?? $paymentMethod;

            return $this->persistService->save(
                $methodData,
                $user,
                $paymentMethodData
            );
        });
    }

    private function attachToCustomerIfSupported(
        object $provider,
        string $methodKey,
        Authenticatable $user,
        ?string $type
    ): mixed {
        try {
            if (method_exists($provider, 'attachPaymentMethodToCustomer')) {
                return $provider->attachPaymentMethodToCustomer($methodKey, $user, $type);
            }

            return null;
        } catch (\BadMethodCallException $e) {
            // Provider may not support attachment
            return null;
        }
    }
}
