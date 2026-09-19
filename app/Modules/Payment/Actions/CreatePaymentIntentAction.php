<?php

declare(strict_types=1);

namespace App\Modules\Payment\Actions;

use App\Models\User;
use App\Modules\Payment\Contracts\PaymentGatewayFactoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Psr\Log\LoggerInterface;

final class CreatePaymentIntentAction
{
    public function __construct(
        private readonly PaymentGatewayFactoryInterface $gatewayFactory,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  User  $user
     * @return array<string, mixed>
     */
    public function execute(array $data, Authenticatable $user): array
    {
        /** @var User $user */
        $gateway = isset($data['gateway']) && is_string($data['gateway']) ? $data['gateway'] : 'stripe';

        try {
            $provider = $this->gatewayFactory->create($gateway);

            // Add customer info if not provided
            if (! isset($data['customer_id'])) {
                $customer = $provider->createCustomer([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ]);
                $data['customer_id'] = $customer['customer_id'];
            }

            return $provider->createPayment($data);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid payment intent creation attempt', [
                'user_id' => $user->id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            throw new \InvalidArgumentException('Invalid payment request: '.$e->getMessage());
        } catch (\RuntimeException $e) {
            $this->logger->error('Payment intent creation failed', [
                'user_id' => $user->id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Payment creation failed. Please try again later.');
        } catch (\Exception $e) {
            $this->logger->critical('Unexpected error during payment intent creation', [
                'user_id' => $user->id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw new \RuntimeException('An unexpected error occurred. Please contact support.');
        }
    }
}
