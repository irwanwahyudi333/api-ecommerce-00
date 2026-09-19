<?php

declare(strict_types=1);

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Modules\Payment\Actions\CreatePaymentIntentAction;
use App\Modules\PaymentIntent\Http\Requests\CreatePaymentIntentRequest;
use Illuminate\Http\JsonResponse;

final class PaymentIntentController extends BaseController
{
    public function __construct(
        private readonly CreatePaymentIntentAction $createPaymentIntentAction,
    ) {}

    /**
     * @OA\Post(
     *     path="/payment-intents",
     *     tags={"Payment Intents"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/CreatePaymentIntentRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Payment intent created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="id", type="string"),
     *             @OA\Property(property="client_secret", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="currency", type="string"),
     *             @OA\Property(property="payment_method_type", type="string", nullable=true)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(CreatePaymentIntentRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $intent = $this->createPaymentIntentAction->execute(
            $request->validated(),
            $user
        );

        return response()->json($intent, 201);
    }
}
