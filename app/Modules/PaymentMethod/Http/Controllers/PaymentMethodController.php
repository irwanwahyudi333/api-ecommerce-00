<?php

declare(strict_types=1);

namespace App\Modules\PaymentMethod\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Modules\PaymentMethod\DTO\PaymentMethodData;
use App\Modules\PaymentMethod\Http\Requests\PaymentMethodCreateRequest;
use App\Modules\PaymentMethod\Http\Requests\SavePaymentMethodRequest;
use App\Modules\PaymentMethod\Http\Requests\SetDefaultCardRequest;
use App\Modules\PaymentMethod\Http\Resources\PaymentMethodResource;
use App\Modules\PaymentMethod\Services\PaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentMethodController extends BaseController
{
    public function __construct(private PaymentMethodService $pmService) {}

    /**
     * GET /payment-methods
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $gateway = $request->query('gateway');
        /** @var User $user */
        $user = $request->user();
        if ($gateway) {
            $methods = $this->pmService->getUserPaymentMethodsByGateway($user, (string) $gateway);
        } else {
            $methods = $this->pmService->getUserPaymentMethods($user);
        }

        return PaymentMethodResource::collection($methods);
    }

    public function show(int $id): PaymentMethodResource
    {
        $method = PaymentMethod::findOrFail($id); // Assuming ID is unique for settings
        $this->authorize('view', $method);

        return new PaymentMethodResource($method);
    }

    /**
     * GET /payment-methods/gateways
     */
    public function gateways(Request $request): JsonResponse
    {
        return response()->json([
            'gateways' => $this->pmService->getAvailableGateways(),
        ]);
    }

    /**
     * POST /payment-methods
     */
    public function store(PaymentMethodCreateRequest $request): PaymentMethodResource
    {
        $this->authorize('create', PaymentMethod::class);

        $data = PaymentMethodData::fromRequest($request->validated());
        /** @var User $user */
        $user = $request->user();
        $method = $this->pmService->storePaymentMethod($data, $user);

        return new PaymentMethodResource($method);
    }

    /**
     * DELETE /payment-methods/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $method = PaymentMethod::findOrFail($id);
        $this->authorize('delete', $method);

        $this->pmService->deletePaymentMethod($id);

        return $this->sendSuccess(null, 'Payment method deleted successfully');
    }

    /**
     * POST /payment-methods/save
     */
    public function savePaymentMethod(SavePaymentMethodRequest $request): PaymentMethodResource
    {
        $this->authorize('create', PaymentMethod::class);

        $method = $this->pmService->savePaymentMethod($request);

        return new PaymentMethodResource($method);
    }

    /**
     * POST /payment-methods/setup-intent
     */
    public function saveCardIntent(Request $request): JsonResponse
    {
        $this->authorize('create', PaymentMethod::class);

        $request->validate([
            'gateway' => ['nullable', 'string', 'in:stripe,midtrans,xendit'],
        ]);

        $gatewayInput = $request->input('gateway', 'xendit');
        $gateway = is_string($gatewayInput) ? $gatewayInput : 'xendit';
        /** @var User $user */
        $user = $request->user();
        $intent = $this->pmService->initializePaymentMethod($user, $gateway);

        return response()->json($intent ?? ['status' => 'not_supported']);
    }

    /**
     * POST /payment-methods/set-default
     */
    public function setDefaultCard(SetDefaultCardRequest $request): PaymentMethodResource
    {
        $methodId = $request->input('method_id');
        $method = $this->pmService->setDefaultPayment(is_numeric($methodId) ? (int) $methodId : 0);
        $this->authorize('setDefault', $method);

        return new PaymentMethodResource($method);
    }
}
