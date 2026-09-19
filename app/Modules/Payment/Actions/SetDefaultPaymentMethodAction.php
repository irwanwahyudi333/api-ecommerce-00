<?php

declare(strict_types=1);

namespace App\Modules\Payment\Actions;

use App\Models\PaymentMethod;
use App\Modules\PaymentMethod\Events\PaymentMethodUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class SetDefaultPaymentMethodAction
{
    public function execute(PaymentMethod $method): PaymentMethod
    {
        return DB::transaction(function () use ($method) {
            // Reset all defaults for this user's payment gateway
            PaymentMethod::where('payment_gateway_id', $method->payment_gateway_id)
                ->where('id', '!=', $method->id)
                ->update(['default_payment' => false]);

            // Set this method as default
            $method->default_payment = true;
            $method->save();

            Event::dispatch(new PaymentMethodUpdated($method));

            /** @var PaymentMethod $freshMethod */
            $freshMethod = $method->fresh() ?? $method;

            return $freshMethod;
        });
    }
}
