<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\DownloadToken;
use App\Models\Order;
use Illuminate\Support\Str;

class OrderIdentityService
{
    public function generateTrackingNumber(): string
    {
        $today = date('Ymd');
        do {
            $trackingNumber = $today.random_int(100000, 999999);
        } while (Order::where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }

    public function getExportToken(int $userId, ?int $shopId): string
    {
        $token = DownloadToken::create([
            'user_id' => $userId,
            'token' => Str::random(32),
            'payload' => (string) $shopId,
        ]);

        return route('export_order.token', ['token' => $token->token]);
    }

    /**
     * @param  array<mixed>  $translatedText
     */
    public function getInvoiceTokenSecure(int $userId, int $orderId, string $language, array $translatedText, bool $isRtl): string
    {
        $payload = json_encode([
            'user_id' => $userId,
            'order_id' => $orderId,
            'language' => $language,
            'translated_text' => $translatedText,
            'is_rtl' => $isRtl,
        ], JSON_THROW_ON_ERROR);

        $token = DownloadToken::create([
            'user_id' => $userId,
            'token' => Str::random(32),
            'payload' => $payload,
        ]);

        return route('download_invoice.token', ['token' => $token->token]);
    }
}
