<?php

declare(strict_types=1);

namespace App\Modules\Payment\Factory;

use App\Modules\Payment\Contracts\PaymentGatewayFactoryInterface;
use App\Modules\Payment\Contracts\PaymentProviderInterface;
use App\Modules\Payment\Providers\StripeProvider;
use App\Services\Payment\Providers\MidtransProvider;
use App\Services\Payment\Providers\XenditProvider;
use Illuminate\Support\Facades\Cache;

final class PaymentGatewayFactory implements PaymentGatewayFactoryInterface
{
    private const CACHE_KEY = 'payment.gateways.available';

    private const CACHE_TTL = 86400; // 24 hours

    /**
     * @var array<string, string>
     */
    private array $gatewayMap = [
        'stripe' => StripeProvider::class,
        'midtrans' => MidtransProvider::class,
        'xendit' => XenditProvider::class,
    ];

    public function create(string $gateway): PaymentProviderInterface
    {
        if (! $this->supports($gateway)) {
            throw new \InvalidArgumentException(
                sprintf('Payment gateway "%s" is not supported.', $gateway)
            );
        }

        $providerClass = $this->gatewayMap[$gateway];

        if (! class_exists($providerClass)) {
            throw new \RuntimeException(
                sprintf('Payment provider class "%s" does not exist.', $providerClass)
            );
        }

        /** @var PaymentProviderInterface $provider */
        $provider = app($providerClass);

        return $provider;
    }

    public function getAvailableGateways(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $available = [];

            foreach ($this->gatewayMap as $gateway => $providerClass) {
                if ($this->isGatewayEnabled($gateway)) {
                    $available[] = $gateway;
                }
            }

            return $available;
        });
    }

    public function supports(string $gateway): bool
    {
        return isset($this->gatewayMap[$gateway]) && $this->isGatewayEnabled($gateway);
    }

    private function isGatewayEnabled(string $gateway): bool
    {
        $configKey = "services.{$gateway}.enabled";
        $isEnabled = config($configKey, true);

        // Additional check for required configuration
        $secretKey = config("services.{$gateway}.secret");
        $apiKey = config("services.{$gateway}.api_key");

        return $isEnabled && ($secretKey || $apiKey);
    }
}
