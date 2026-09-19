<?php

declare(strict_types=1);

namespace App\Modules\Otp\Services;

use App\Modules\Otp\Contracts\OtpInterface;
use App\Modules\Otp\DTO\OtpResult;
use Illuminate\Support\Facades\Config;

final class OtpService
{
    private ?OtpInterface $gateway = null;

    public function __construct()
    {
        /** @var string $gatewayName */
        $gatewayName = Config::get('auth.active_otp_gateway', 'twilio');
        // Use the new modular path for gateways
        $gatewayClass = 'App\\Modules\\Otp\\Services\\Gateways\\'.ucfirst($gatewayName).'Gateway';

        if (class_exists($gatewayClass)) {
            // Instantiate the gateway if it's not commented out
            // For now, these gateways are commented out, so this will effectively disable OTP
            // You would uncomment the gateway class and its dependencies to enable them
            // $this->gateway = new $gatewayClass();
        } else {
            // Log or handle the case where the gateway class doesn't exist
            // For now, we'll leave $this->gateway as null and throw an exception in methods
        }
    }

    public function startVerification(string $phoneNumber): OtpResult
    {
        if (! $this->gateway) {
            throw new \Exception('OTP service is disabled or misconfigured.');
        }

        return $this->gateway->startVerification($phoneNumber);
    }

    public function checkVerification(string $id, string $code, string $phoneNumber): OtpResult
    {
        if (! $this->gateway) {
            throw new \Exception('OTP service is disabled or misconfigured.');
        }

        return $this->gateway->checkVerification($id, $code, $phoneNumber);
    }

    public function sendSms(string $phoneNumber, string $message): OtpResult
    {
        if (! $this->gateway) {
            throw new \Exception('OTP service is disabled or misconfigured.');
        }

        return $this->gateway->sendSms($phoneNumber, $message);
    }

    public function setGateway(OtpInterface $gateway): void
    {
        $this->gateway = $gateway;
    }
}
