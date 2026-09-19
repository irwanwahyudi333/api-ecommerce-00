<?php

declare(strict_types=1);

namespace App\Modules\Payment\Services;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class PaymentMethodQueryService
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * @return Collection<int, PaymentMethod>
     */
    public function getUserPaymentMethods(Authenticatable $user): Collection
    {
        /** @var User $user */
        $cacheKey = "payment_methods.user.{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return PaymentMethod::query()
                ->whereHas('paymentGateway', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['paymentGateway'])
                ->orderBy('default_payment', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    public function getUserPaymentMethodsByGateway(Authenticatable $user, string $gateway): Collection
    {
        /** @var User $user */
        $cacheKey = "payment_methods.user.{$user->id}.gateway.{$gateway}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $gateway) {
            return PaymentMethod::query()
                ->whereHas('paymentGateway', function ($query) use ($user, $gateway) {
                    $query->where('user_id', $user->id)
                        ->where('gateway_name', strtolower($gateway));
                })
                ->with(['paymentGateway'])
                ->orderBy('default_payment', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    public function getUserPaymentMethodsByType(Authenticatable $user, string $type): Collection
    {
        /** @var User $user */
        $cacheKey = "payment_methods.user.{$user->id}.type.{$type}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $type) {
            return PaymentMethod::query()
                ->whereHas('paymentGateway', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->where('method_type', $type)
                ->with(['paymentGateway'])
                ->orderBy('default_payment', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    public function findByIdAndUser(int $id, Authenticatable $user): ?PaymentMethod
    {
        /** @var User $user */
        return PaymentMethod::query()
            ->where('id', $id)
            ->whereHas('paymentGateway', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['paymentGateway'])
            ->first();
    }

    public function clearUserCache(Authenticatable $user): void
    {
        /** @var User $user */
        $userId = $user->id;
        Cache::forget("payment_methods.user.{$userId}");
        Cache::forget("payment_methods.user.{$userId}.gateway.stripe");
        Cache::forget("payment_methods.user.{$userId}.gateway.midtrans");
        Cache::forget("payment_methods.user.{$userId}.gateway.xendit");
    }
}
