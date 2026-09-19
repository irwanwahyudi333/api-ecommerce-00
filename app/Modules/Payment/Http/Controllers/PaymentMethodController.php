<?php

declare(strict_types=1);

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Modules\Payment\Actions\DeletePaymentMethodAction;
use App\Modules\Payment\Actions\GetPaymentMethodsAction;
use App\Modules\Payment\Actions\InitializePaymentMethodAction;
use App\Modules\Payment\Actions\SetDefaultPaymentMethodAction;
use App\Modules\Payment\Actions\StorePaymentMethodAction;
use App\Modules\Payment\Http\Requests\PaymentGatewayRequest;
use App\Modules\Payment\Http\Requests\PaymentMethodDeleteRequest;
use App\Modules\PaymentMethod\Http\Requests\PaymentMethodStoreRequest;
use App\Modules\PaymentMethod\Http\Requests\SetDefaultPaymentMethodRequest;
use App\Modules\PaymentMethod\Http\Resources\PaymentMethodResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentMethodController extends BaseController
{
    public function __construct(
        private readonly GetPaymentMethodsAction $getPaymentMethodsAction,
        private readonly StorePaymentMethodAction $storePaymentMethodAction,
        private readonly DeletePaymentMethodAction $deletePaymentMethodAction,
        private readonly SetDefaultPaymentMethodAction $setDefaultPaymentMethodAction,
        private readonly InitializePaymentMethodAction $initializePaymentMethodAction,
    ) {}

    /**
     * @OA\Get(
     *     path="/payment-methods",
     *     tags={"Payment Methods"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of user's payment methods",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/PaymentMethodResource")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PaymentMethod::class);

        $gateway = $request->query('gateway');
        /** @var User $user */
        $user = $request->user();
        $methods = $this->getPaymentMethodsAction->execute($user, is_string($gateway) ? $gateway : null);

        return PaymentMethodResource::collection($methods)->response();
    }

    /**
     * @OA\Get(
     *     path="/payment-methods/gateways",
     *     tags={"Payment Methods"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of available payment gateways",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="gateways", type="array", @OA\Items(type="string"))
     *         )
     *     )
     * )
     */
    public function gateways(): JsonResponse
    {
        $gateways = config('payment.gateways', ['stripe', 'midtrans', 'xendit']);

        return response()->json(['gateways' => $gateways]);
    }

    /**
     * @OA\Post(
     *     path="/payment-methods",
     *     tags={"Payment Methods"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/PaymentMethodStoreRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Payment method created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/PaymentMethodResource")
     *     )
     * )
     */
    public function store(PaymentMethodStoreRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentMethod::class);

        /** @var User $user */
        $user = $request->user();

        $method = $this->storePaymentMethodAction->execute(
            $request->validated(),
            $user
        );

        return (new PaymentMethodResource($method))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Delete(
     *     path="/payment-methods/{id}",
     *     tags={"Payment Methods"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=204,
     *         description="Payment method deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment method not found"
     *     )
     * )
     */
    public function destroy(PaymentMethodDeleteRequest $request): JsonResponse
    {
        $method = $request->getPaymentMethod();

        $this->deletePaymentMethodAction->execute($method);

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/payment-methods/setup-intent",
     *     tags={"Payment Methods"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/PaymentGatewayRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment method initialization data",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="client_secret", type="string", nullable=true),
     *             @OA\Property(property="token", type="string", nullable=true),
     *             @OA\Property(property="redirect_url", type="string", nullable=true)
     *         )
     *     )
     * )
     */
    public function setupIntent(PaymentGatewayRequest $request): JsonResponse
    {
        $this->authorize('create', PaymentMethod::class);

        /** @var User $user */
        $user = $request->user();
        /** @var string $gateway */
        $gateway = $request->validated()['gateway'];

        $intent = $this->initializePaymentMethodAction->execute(
            $user,
            $gateway
        );

        return response()->json($intent ?? ['status' => 'not_supported']);
    }

    /**
     * @OA\Post(
     *     path="/payment-methods/set-default",
     *     tags={"Payment Methods"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/SetDefaultPaymentMethodRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Payment method set as default",
     *
     *         @OA\JsonContent(ref="#/components/schemas/PaymentMethodResource")
     *     )
     * )
     */
    public function setDefault(SetDefaultPaymentMethodRequest $request): JsonResponse
    {
        /** @var PaymentMethod $method */
        $method = $request->getPaymentMethod();

        $this->authorize('setDefault', $method);

        $updatedMethod = $this->setDefaultPaymentMethodAction->execute($method);

        return (new PaymentMethodResource($updatedMethod))->response();
    }
}
