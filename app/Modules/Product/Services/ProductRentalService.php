<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Models\Availability;
use App\Models\Product;
use App\Models\Resource;
use App\Models\Variation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;

class ProductRentalService
{
    /**
     * @return array<int, int>
     */
    public function getUnavailableProductIds(string $from, string $to): array
    {
        $availabilities = Availability::whereDate('from', '<=', $from)
            ->whereDate('to', '>=', $to)
            ->get()
            ->groupBy('product_id');

        $unavailable = [];
        foreach ($availabilities as $productId => $items) {
            if (! $this->isProductAvailable($from, $to, (int) $productId, $items)) {
                $unavailable[] = (int) $productId;
            }
        }

        return $unavailable;
    }

    /**
     * @param  iterable<mixed, mixed>  $blockedDates
     */
    public function isProductAvailable(string $from, string $to, int $productId, iterable $blockedDates, int $requestedQuantity = 1): bool
    {
        /** @var Product $product */
        $product = Product::findOrFail($productId);
        $totalBooked = 0;

        foreach ($blockedDates as $bd) {
            if (! is_object($bd) || ! property_exists($bd, 'from') || ! property_exists($bd, 'to')) {
                continue;
            }
            /** @var string|\DateTimeInterface $fromBd */
            $fromBd = $bd->from;
            /** @var string|\DateTimeInterface $toBd */
            $toBd = $bd->to;
            $period = Period::make(
                $fromBd,
                $toBd,
                Precision::DAY(),
                Boundaries::EXCLUDE_END()
            );

            $range = Period::make(
                $from,
                $to,
                Precision::DAY(),
                Boundaries::EXCLUDE_END()
            );

            if ($period->overlapsWith($range)) {
                $orderQuantity = property_exists($bd, 'order_quantity') && is_numeric($bd->order_quantity) ? (int) $bd->order_quantity : 0;
                $totalBooked += $orderQuantity;
            }
        }

        return ($product->quantity - $totalBooked) >= $requestedQuantity;
    }

    /**
     * @return array<string, float>
     *
     * @throws ValidationException
     */
    public function calculateRentalPrice(Request $request): array
    {
        $productId = is_numeric($request->product_id) ? (int) $request->product_id : 0;
        /** @var Product $product */
        $product = Product::findOrFail($productId);
        if (! $product->is_rental) {
            throw ValidationException::withMessages([
                'product_id' => [config('notice.NOT_A_RENTAL_PRODUCT')],
            ]);
        }

        $fromStr = is_string($request->from) ? $request->from : '';
        $toStr = is_string($request->to) ? $request->to : '';
        $from = Carbon::parse($fromStr);
        $to = Carbon::parse($toStr);
        $bookedDays = $from->diffInDays($to);
        $quantity = is_numeric($request->quantity) ? (int) $request->quantity : 1;

        $persons = $this->extractIntArray($request->persons);
        $features = $this->extractIntArray($request->features);
        $deposits = $this->extractIntArray($request->deposits);

        if ($request->filled('variation_id')) {
            $variationId = is_numeric($request->variation_id) ? (int) $request->variation_id : 0;
            /** @var Variation $variation */
            $variation = Variation::findOrFail($variationId);
            $basePrice = ((float) ($variation->sale_price ?: $variation->price)) * $bookedDays * $quantity;
        } else {
            $basePrice = ((float) ($product->sale_price ?: $product->price)) * $bookedDays * $quantity;
        }

        $personPrice = $this->sumResourcePrices($persons);
        $featurePrice = $this->sumResourcePrices($features);
        $depositPrice = $this->sumResourcePrices($deposits);
        $dropoffId = is_numeric($request->dropoff_location_id) ? (int) $request->dropoff_location_id : 0;
        $dropoffPrice = $request->filled('dropoff_location_id') ? $this->getResourcePrice($dropoffId) : 0.0;
        $pickupId = is_numeric($request->pickup_location_id) ? (int) $request->pickup_location_id : 0;
        $pickupPrice = $request->filled('pickup_location_id') ? $this->getResourcePrice($pickupId) : 0.0;

        return [
            'totalPrice' => (float) ($basePrice + $personPrice + $depositPrice + $featurePrice + $dropoffPrice + $pickupPrice),
            'personPrice' => $personPrice,
            'depositPrice' => $depositPrice,
            'featurePrice' => $featurePrice,
            'dropoffLocationPrice' => $dropoffPrice,
            'pickupLocationPrice' => $pickupPrice,
        ];
    }

    /**
     * @param  array<int, int>  $resourceIds
     */
    private function sumResourcePrices(array $resourceIds): float
    {
        if (empty($resourceIds)) {
            return 0.0;
        }

        return (float) Resource::whereIn('id', $resourceIds)->sum('price');
    }

    private function getResourcePrice(int $id): float
    {
        $resource = Resource::find($id);

        return $resource ? (float) $resource->price : 0.0;
    }

    /**
     * @return array<int, int>
     */
    private function extractIntArray(mixed $data): array
    {
        $result = [];
        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_numeric($item)) {
                    $result[] = (int) $item;
                }
            }
        }

        return $result;
    }
}
