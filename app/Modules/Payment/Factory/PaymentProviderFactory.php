<?php

declare(strict_types=1);

namespace App\Modules\Payment\Factory;

use App\Services\Payment\Contracts\PaymentProviderInterface;
use App\Services\Payment\Providers\MidtransProvider;
use App\Services\Payment\Providers\StripeProvider;
use App\Services\Payment\Providers\XenditProvider;
use InvalidArgumentException;

class PaymentProviderFactory
{
    public const GATEWAYS = [
        'stripe' => StripeProvider::class,
        'midtrans' => MidtransProvider::class,
        'xendit' => XenditProvider::class,
    ];

    public static function create(string $gateway): PaymentProviderInterface
    {
        $gateway = strtolower($gateway);

        if (! isset(self::GATEWAYS[$gateway])) {
            throw new InvalidArgumentException("Unsupported payment gateway: {$gateway}");
        }

        $class = self::GATEWAYS[$gateway];

        $classInstance = app($class);
        if (! $classInstance instanceof PaymentProviderInterface) {
            throw new InvalidArgumentException("Provider {$class} must implement PaymentProviderInterface");
        }

        return $classInstance;
    }

    /**
     * @return array<int, string>
     */
    public static function getAvailableGateways(): array
    {
        return array_keys(self::GATEWAYS);
    }
}
