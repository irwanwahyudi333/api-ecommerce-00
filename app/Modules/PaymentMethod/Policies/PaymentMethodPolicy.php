<?php

declare(strict_types=1);

namespace App\Modules\PaymentMethod\Policies;

use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function before(?User $user, string $ability): ?bool
    {
        if ($user && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, PaymentMethod $method): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('payment_method.create');
    }

    public function update(User $user, PaymentMethod $method): bool
    {
        if (! $method->paymentGateway) {
            return false;
        }

        /** @var PaymentGateway $gateway */
        $gateway = $method->paymentGateway;

        return $gateway->user_id === $user->id
            && $user->hasPermissionTo('payment_method.update');
    }

    public function delete(User $user, PaymentMethod $method): bool
    {
        if (! $method->paymentGateway) {
            return false;
        }

        /** @var PaymentGateway $gateway */
        $gateway = $method->paymentGateway;

        return $gateway->user_id === $user->id
            && $user->hasPermissionTo('payment_method.delete');
    }

    public function setDefault(User $user, PaymentMethod $method): bool
    {
        if (! $method->paymentGateway) {
            return false;
        }

        /** @var PaymentGateway $gateway */
        $gateway = $method->paymentGateway;

        return $gateway->user_id === $user->id
            && $user->hasPermissionTo('payment_method.set_default');
    }

    public function restore(User $user, PaymentMethod $method): bool
    {
        return $user->hasPermissionTo('payment_method.restore');
    }

    public function forceDelete(User $user, PaymentMethod $method): bool
    {
        return $user->hasPermissionTo('payment_method.force_delete');
    }
}
