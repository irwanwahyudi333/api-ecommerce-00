<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers;

use App\Exports\OrderExport;
use App\Http\Controllers\BaseController;
use App\Models\DownloadToken;
use App\Models\Order;
use App\Models\Settings;
use App\Modules\Address\Services\AddressFormatterService;
use App\Modules\Order\Actions\CreateOrderAction;
use App\Modules\Order\Actions\UpdateOrderStatusAction;
use App\Modules\Order\DTO\OrderData;
use App\Modules\Order\Http\Requests\CreateOrderRequest;
use App\Modules\Order\Http\Requests\UpdateOrderRequest;
use App\Modules\Order\Http\Resources\OrderResource;
use App\Modules\Order\Services\OrderIdentityService;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Settings\Services\SettingsService;
use App\Services\CurrencyFormatterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends BaseController
{
    public function __construct(
        private OrderService $orderService,
        private OrderIdentityService $identityService,
        private PaymentService $paymentService,
        private CreateOrderAction $createOrderAction,
        private UpdateOrderStatusAction $updateOrderStatusAction,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);
        $user = $request->user();
        $limit = is_numeric($request->limit) ? (int) $request->limit : 10;
        $user = $request->user();
        if (! $user) {
            throw new \Exception('Unauthenticated');
        }
        $orders = $this->orderService->getOrdersQuery($request, $user)->paginate($limit);

        return OrderResource::collection($orders);
    }

    public function store(CreateOrderRequest $request): OrderResource
    {
        $this->authorize('create', Order::class);
        $settings = Settings::first();
        if (! $settings instanceof Settings) {
            throw new \Exception('Settings not found');
        }
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $data = OrderData::fromRequest($validated);
        $user = $request->user();
        if (! $user) {
            throw new \Exception('Unauthenticated');
        }
        $order = $this->createOrderAction->execute($data, $settings, $user);

        return new OrderResource($order);
    }

    public function show(Request $request, string $params): OrderResource
    {
        $defaultLang = is_string(config('shop.default_language', 'id')) ? config('shop.default_language', 'id') : 'id';
        $language = is_string($request->language) ? $request->language : $defaultLang;
        $user = $request->user();
        if (! $user) {
            throw new \Exception('Unauthenticated');
        }
        $order = $this->orderService->getOrderByTrackingOrId($params, $language, $user);
        $this->authorize('view', $order);

        if (! in_array($order->payment_gateway, ['cash', 'cash_on_delivery', 'full_wallet_payment'], true)) {
            $tracking = is_scalar($order->tracking_number) ? (string) $order->tracking_number : '';
            $order->setAttribute('payment_intent', $this->paymentService->attachPaymentIntent($tracking));
        }

        return new OrderResource($order);
    }

    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $this->authorize('update', $order);
        $status = is_string($request->order_status) ? $request->order_status : '';
        $updated = $this->updateOrderStatusAction->execute($order, $status);

        return $this->sendSuccess(new OrderResource($updated), 'Order updated');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $this->authorize('delete', $order);
        $order->delete();

        return $this->sendSuccess(null, 'Order deleted');
    }

    public function exportOrderUrl(Request $request, ?int $shop_id = null): JsonResponse
    {
        $this->authorize('export', [Order::class, $shop_id]);
        $user = $request->user();
        if (! $user) {
            throw new \Exception('Unauthenticated');
        }
        $url = $this->identityService->getExportToken((int) $user->id, $shop_id);

        return response()->json(['url' => $url]);
    }

    public function exportOrder(Request $request, string $token): BinaryFileResponse
    {
        $user = $request->user();
        $downloadToken = DownloadToken::where('token', $token)
            ->where('user_id', $user ? $user->getAuthIdentifier() : null)
            ->firstOrFail();

        $payload = is_array($downloadToken->payload) ? $downloadToken->payload : [];
        $shopId = $payload['shop_id'] ?? null;
        $shopId = is_numeric($shopId) ? (int) $shopId : null;
        $downloadToken->delete();

        $query = Order::with(['customer', 'shop']);
        if ($shopId) {
            $query->where('shop_id', $shopId);
        } else {
            $query->whereNull('parent_id');
        }
        $orders = $query->get();

        $addressFormatter = app(AddressFormatterService::class);
        $currencyFormatter = app(CurrencyFormatterService::class);
        $settingsService = app(SettingsService::class);

        return Excel::download(
            new OrderExport($orders, $shopId, $addressFormatter, $currencyFormatter, $settingsService),
            'orders.xlsx'
        );
    }

    public function downloadInvoiceUrl(Request $request): JsonResponse
    {
        $this->authorize('export', [Order::class, $request->shop_id]);
        $request->validate(['order_id' => 'required|integer']);
        $user = $request->user();
        if (! $user) {
            throw new \Exception('Unauthenticated');
        }
        $language = is_string($request->language) ? $request->language : config('shop.default_language', 'id');
        $isRtl = (bool) $request->is_rtl;
        $translatedText = is_array($request->translated_text) ? $request->translated_text : [];

        $url = $this->identityService->getInvoiceTokenSecure(
            $user->id,
            is_numeric($request->order_id) ? (int) $request->order_id : 0,
            is_scalar($language) ? (string) $language : '',
            $translatedText,
            $isRtl
        );

        return response()->json(['url' => $url]);
    }

    public function downloadInvoice(Request $request, string $token): Response
    {
        $user = $request->user();
        $downloadToken = DownloadToken::where('token', $token)
            ->where('user_id', $user ? $user->getAuthIdentifier() : null)
            ->firstOrFail();

        $payload = is_array($downloadToken->payload) ? $downloadToken->payload : [];
        $downloadToken->delete();

        $orderId = isset($payload['order_id']) ? $payload['order_id'] : null;

        $order = Order::with(['products', 'children.shop', 'parent_order', 'wallet_point'])
            ->where('id', $orderId)
            ->orWhere('tracking_number', $orderId)
            ->firstOrFail();

        $settings = Settings::getData(isset($payload['language']) && is_scalar($payload['language']) ? (string) $payload['language'] : (is_string(config('shop.default_language', 'id')) ? config('shop.default_language', 'id') : 'id'));
        $invoiceData = [
            'order' => $order,
            'settings' => $settings,
            'translated_text' => isset($payload['translated_text']) && is_array($payload['translated_text']) ? $payload['translated_text'] : [],
            'is_rtl' => isset($payload['is_rtl']) ? (bool) $payload['is_rtl'] : false,
            'language' => isset($payload['language']) && is_scalar($payload['language']) ? (string) $payload['language'] : 'id',
        ];
        $pdf = Pdf::loadView('pdf.order-invoice', $invoiceData);

        return $pdf->download('invoice-order-'.(is_scalar($orderId) ? $orderId : 'unknown').'.pdf');
    }
}
