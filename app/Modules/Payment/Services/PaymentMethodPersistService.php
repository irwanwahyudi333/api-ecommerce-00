<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Modules\PaymentMethod\DTO\PaymentMethodData;
use App\Modules\PaymentMethod\Events\PaymentMethodCreated;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

final class PaymentMethodPersistService
{
    public function save(object $paymentMethodData, Authenticatable $user, PaymentMethodData $data): PaymentMethod
    {
        /** @var User $user */
        $paymentGateway = PaymentGateway::firstOrCreate([
            'user_id' => $user->id,
            'gateway_name' => $data->payment_gateway,
        ]);

        $method = PaymentMethod::create([
            'payment_gateway_id' => $paymentGateway->id,
            'method_key' => $data->method_key,
            'method_type' => $data->method_type,
            'default_payment' => $data->default_payment,
            'brand' => $data->brand,
            'last4' => $data->last4,
            'exp_month' => $data->exp_month,
            'exp_year' => $data->exp_year,
            'va_number' => $data->va_number,
            'bank_code' => $data->bank_code,
            'qris_url' => $data->qris_url,
            'ewallet_type' => $data->ewallet_type,
            'account_name' => $data->account_name,
            'account_number' => $data->account_number,
            'metadata' => $data->metadata,
            'provider_data' => json_encode($paymentMethodData),
        ]);

        // Clear cache
        $this->clearUserCache($user);

        // Dispatch event
        Event::dispatch(new PaymentMethodCreated($method));

        return $method;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(PaymentMethod $method, array $attributes): PaymentMethod
    {
        $method->update($attributes);

        // Clear cache for this user
        /** @var PaymentGateway|null $paymentGateway */
        $paymentGateway = $method->paymentGateway;
        if ($paymentGateway && $paymentGateway->user_id) {
            Cache::forget("payment_methods.user.{$paymentGateway->user_id}");
        }

        /** @var PaymentMethod $freshMethod */
        $freshMethod = $method->fresh();

        return $freshMethod;
    }

    public function delete(PaymentMethod $method): void
    {
        /** @var PaymentGateway|null $paymentGateway */
        $paymentGateway = $method->paymentGateway;
        $userId = $paymentGateway?->user_id;

        $method->delete();

        // Clear cache
        if ($userId) {
            Cache::forget("payment_methods.user.{$userId}");
        }
    }

    private function clearUserCache(Authenticatable $user): void
    {
        /** @var User $user */
        Cache::forget("payment_methods.user.{$user->id}");
    }
}
