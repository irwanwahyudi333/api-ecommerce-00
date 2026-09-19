<?php

declare(strict_types=1);

namespace App\Modules\Payment\Actions;

use App\Models\User;
use App\Modules\Payment\Contracts\PaymentGatewayFactoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Psr\Log\LoggerInterface;

final class InitializePaymentMethodAction
{
    public function __construct(
        private readonly PaymentGatewayFactoryInterface $gatewayFactory,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param  User  $user
     * @return array<string, mixed>|null
     */
    public function execute(Authenticatable $user, string $gateway): ?array
    {
        /** @var User $user */
        try {
            $provider = $this->gatewayFactory->create($gateway);

            $customerData = [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ];

            // Create customer if gateway requires it
            if (in_array($gateway, ['stripe', 'xendit'], true)) {
                $customer = $provider->createCustomer($customerData);
                $customerData['customer_id'] = $customer['customer_id'];
            }

            return $provider->initializePaymentMethod($customerData);
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize payment method', [
                'user_id' => $user->id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
