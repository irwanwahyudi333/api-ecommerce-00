<?php

declare(strict_types=1);

namespace App\Modules\Payment\Actions;

use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Modules\Payment\Contracts\PaymentGatewayFactoryInterface;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

final class DeletePaymentMethodAction
{
    public function __construct(
        private readonly PaymentGatewayFactoryInterface $gatewayFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(PaymentMethod $method): void
    {
        DB::transaction(function () use ($method) {
            /** @var PaymentGateway|null $paymentGateway */
            $paymentGateway = $method->paymentGateway;
            $gatewayName = $paymentGateway ? (string) $paymentGateway->gateway_name : '';

            $provider = $this->gatewayFactory->create($gatewayName);

            try {
                $provider->detachPaymentMethod($method->method_key, $method->method_type);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to detach payment method from gateway', [
                    'method_id' => $method->id,
                    'gateway' => $gatewayName,
                    'error' => $e->getMessage(),
                ]);
            }

            $method->forceDelete();
        });
    }
}
