<?php

declare(strict_types=1);

namespace App\Modules\PaymentIntent\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Settings;
use App\Modules\PaymentIntent\Http\Requests\GetPaymentIntentRequest;
use App\Modules\PaymentIntent\Http\Resources\PaymentIntentResource;
use App\Modules\PaymentIntent\Services\PaymentIntentService;
use Illuminate\Auth\AuthenticationException;

class PaymentIntentController extends BaseController
{
    public function __construct(private PaymentIntentService $intentService) {}

    /**
     * GET /payment-intent
     */
    public function getPaymentIntent(GetPaymentIntentRequest $request): PaymentIntentResource
    {
        $settings = Settings::getData();

        // Check if guest checkout is allowed
        if (! $request->user() && ! ($settings->options['guestCheckout'] ?? false)) {
            throw new AuthenticationException;
        }

        $intent = $this->intentService->getOrCreatePaymentIntent($request, $settings);

        return new PaymentIntentResource($intent);
    }
}
