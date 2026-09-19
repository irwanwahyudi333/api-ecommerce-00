<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\Order;
use App\Models\Refund;
use App\Models\Settings;
use App\Models\User;
use App\Modules\Order\Actions\CreateOrderAction;
use App\Modules\Order\Actions\UpdateOrderStatusAction;
use App\Modules\Order\DTO\OrderData;
use App\Modules\Order\Events\OrderDelivered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderTransactionService
{
    public function __construct(
        private CreateOrderAction $createOrder,
        private UpdateOrderStatusAction $updateOrderStatus,
        private OrderCacheService $cacheService,
        private OrderInventoryService $inventoryService
    ) {}

    public function createOrder(OrderData $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user) {
            try {
                // Create order
                $settings = Settings::getData($data->language ?? 'id');
                $order = $this->createOrder->execute($data, $settings, $user);

                // Invalidate cache
                $this->cacheService->invalidateAllOrderCache();

                // Log order creation
                Log::info('Order created', [
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking_number,
                    'user_id' => $user->id,
                    'total' => $order->total,
                    'action' => 'create',
                ]);

                return $order;
            } catch (\Exception $e) {
                Log::error('Order creation failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'data' => (array) $data,
                ]);
                throw $e;
            }
        });
    }

    public function updateOrderStatus(int $orderId, string $status, User $user): Order
    {
        $order = Order::findOrFail($orderId);

        return DB::transaction(function () use ($order, $status, $user) {
            try {
                // Update status
                $updatedOrder = $this->updateOrderStatus->execute($order, $status);

                // Handle status-specific logic
                $this->handleStatusChange($updatedOrder, $status, $user);

                // Invalidate cache
                $this->cacheService->invalidateOrderCache($order->id, $user->id);

                // Log status update
                Log::info('Order status updated', [
                    'order_id' => $order->id,
                    'old_status' => $order->order_status,
                    'new_status' => $status,
                    'user_id' => $user->id,
                    'action' => 'update_status',
                ]);

                return $updatedOrder;
            } catch (\Exception $e) {
                Log::error('Order status update failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
                throw $e;
            }
        });
    }

    public function cancelOrder(int $orderId, User $user, ?string $reason = null): Order
    {
        $order = Order::findOrFail($orderId);

        /** @var Order $result */
        $result = DB::transaction(function () use ($order, $user, $reason) {
            try {
                // Cancel order
                $order->order_status = 'order-cancelled';
                $order->setAttribute('cancelled_at', now());
                if ($reason) {
                    $order->note = $reason;
                }
                $order->save();

                // Restore inventory
                $this->inventoryService->restoreProductInventoryBulk($order);

                // Handle refund if payment was made
                $this->handleRefund($order, $user);

                // Invalidate cache
                $this->cacheService->invalidateOrderCache($order->id, $user->id);

                // Log cancellation
                Log::info('Order cancelled', [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'reason' => $reason,
                    'action' => 'cancel',
                ]);

                return $order->fresh();
            } catch (\Exception $e) {
                Log::error('Order cancellation failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
                throw $e;
            }
        });

        return $result;
    }

    public function updatePaymentStatus(int $orderId, string $paymentStatus, ?string $paymentNote = null, ?User $user = null): Order
    {
        $order = Order::findOrFail($orderId);

        /** @var Order $result */
        $result = DB::transaction(function () use ($order, $paymentStatus, $paymentNote, $user) {
            $order->payment_status = $paymentStatus;
            $order->setAttribute('payment_note', $paymentNote);
            $order->save();

            $this->cacheService->invalidateOrderCache($order->id, $user ? $user->id : 0);

            Log::info('Payment status updated', [
                'order_id' => $order->id,
                'payment_status' => $paymentStatus,
                'user_id' => $user?->id,
                'action' => 'update_payment_status',
            ]);

            return $order->fresh();
        });

        return $result;
    }

    private function handleStatusChange(Order $order, string $status, User $user): void
    {
        switch ($status) {
            case 'order-completed':
                $this->handleOrderCompleted($order, $user);
                break;
            case 'order-cancelled':
                // Tidak perlu memanggil $this->cancelOrder di sini.
                // updateOrderStatus sudah dipanggil dengan status 'order-cancelled'.
                // Handle side effect yang unik jika diperlukan, selain dari yang sudah di handle oleh cancelOrder
                break;
            case 'order-refunded':
                $this->handleRefund($order, $user);
                break;
        }
    }

    private function handleOrderCompleted(Order $order, User $user): void
    {
        // Commission calculation logic
        $commissionRateAttr = $order->getAttribute('commission_rate');
        $commissionRate = is_numeric($commissionRateAttr) ? (float) $commissionRateAttr : 0.0;
        if ($commissionRate > 0) {
            $commission = $order->total * ($commissionRate / 100);
            $order->setAttribute('admin_revenue', $commission);
            $order->setAttribute('shop_revenue', $order->total - $commission);
            $order->save();
        }

        // Emit event for completed order
        event(new OrderDelivered($order));
    }

    private function handleRefund(Order $order, User $user): void
    {
        // Create refund record
        if ($order->payment_status === 'paid') {
            Refund::create([
                'order_id' => $order->id,
                'amount' => $order->total,
                'reason' => 'Order cancelled',
                'status' => 'pending',
            ]);

            $order->payment_status = 'refunded';
            $order->save();
        }
    }
}
