<?php

declare(strict_types=1);

namespace App\Modules\Payment\Providers;

use App\Modules\Payment\Contracts\PaymentProviderInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Psr\Log\LoggerInterface;

abstract class AbstractPaymentProvider implements PaymentProviderInterface
{
    protected string $gatewayName;

    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {}

    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    public function retrievePaymentMethod(string $methodKey, ?string $type = null): object
    {
        throw new \BadMethodCallException(
            sprintf('Method retrievePaymentMethod not implemented for %s', $this->gatewayName)
        );
    }

    public function attachPaymentMethodToCustomer(string $methodKey, Authenticatable $user, ?string $type = null): object
    {
        throw new \BadMethodCallException(
            sprintf('Method attachPaymentMethodToCustomer not implemented for %s', $this->gatewayName)
        );
    }

    public function detachPaymentMethod(string $methodKey, ?string $type = null): void
    {
        // Do nothing by default - not all gateways support detachment
        $this->logger->debug('Payment method detachment not supported', [
            'gateway' => $this->gatewayName,
            'method_key' => $methodKey,
            'type' => $type,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function initializePaymentMethod(array $data): ?array
    {
        return null; // Optional method
    }

    /**
     * @return array<int, string>
     */
    public function getSupportedPaymentMethods(): array
    {
        return []; // Default empty
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyPayment(string $transactionId): array
    {
        throw new \BadMethodCallException(
            sprintf('Method verifyPayment not implemented for %s', $this->gatewayName)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validatePaymentData(array $data): void
    {
        $required = ['amount', 'currency'];

        foreach ($required as $field) {
            if (! isset($data[$field])) {
                throw new \InvalidArgumentException(
                    sprintf('Payment data missing required field: %s', $field)
                );
            }
        }

        if ($data['amount'] <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than 0');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function sanitizeCustomerData(array $data): array
    {
        // Remove sensitive information
        unset(
            $data['password'],
            $data['password_confirmation'],
            $data['ssn'],
            $data['credit_card_number'],
            $data['cvv']
        );

        return $data;
    }
}
