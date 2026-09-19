<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Order;
use App\Models\User;
use App\Modules\Order\DTO\OrderData;
use App\Modules\Order\Http\Requests\CreateOrderRequest;
use App\Modules\Order\Http\Requests\UpdateOrderRequest;
use App\Modules\Order\Http\Resources\OrderResource;
use App\Modules\Order\Services\OrderTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderTransactionController extends BaseController
{
    public function __construct(
        private OrderTransactionService $transactionService
    ) {}

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $user = $request->user();
        if (! $user instanceof User) {
            throw new \Exception('Unauthenticated');
        }
        $data = OrderData::fromRequest($request->validated());
        $order = $this->transactionService->createOrder($data, $user);

        return $this->sendSuccess(
            new OrderResource($order),
            'Order created successfully',
            201
        );
    }

    public function updateStatus(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $this->authorize('update', $order);

        $user = $request->user();
        if (! $user instanceof User) {
            throw new \Exception('Unauthenticated');
        }
        $status = is_string($request->order_status) ? $request->order_status : '';
        $updatedOrder = $this->transactionService->updateOrderStatus(
            $id,
            $status,
            $user
        );

        return $this->sendSuccess(
            new OrderResource($updatedOrder),
            'Order status updated successfully'
        );
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $order = Order::findOrFail($id);
        $this->authorize('update', $order);

        $reason = is_string($request->get('reason')) ? $request->get('reason') : null;
        $user = $request->user();
        if (! $user instanceof User) {
            throw new \Exception('Unauthenticated');
        }
        $cancelledOrder = $this->transactionService->cancelOrder(
            $id,
            $user,
            $reason
        );

        return $this->sendSuccess(
            new OrderResource($cancelledOrder),
            'Order cancelled successfully'
        );
    }

    public function updatePaymentStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'payment_status' => 'required|in:paid,unpaid,refunded,pending',
            'payment_note' => 'nullable|string|max:500',
        ]);

        $this->authorize('update', Order::class);

        $paymentStatus = is_string($request->get('payment_status')) ? $request->get('payment_status') : '';
        $paymentNote = is_string($request->get('payment_note')) ? $request->get('payment_note') : null;
        $user = $request->user();
        if (! $user instanceof User) {
            throw new \Exception('Unauthenticated');
        }

        $updatedOrder = $this->transactionService->updatePaymentStatus(
            $id,
            $paymentStatus,
            $paymentNote,
            $user
        );

        return $this->sendSuccess(
            new OrderResource($updatedOrder),
            'Payment status updated successfully'
        );
    }
}
