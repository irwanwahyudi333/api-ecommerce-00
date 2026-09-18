<?php

declare(strict_types=1);

namespace App\Modules\Checkout\Actions;

use App\Models\Product;
use App\Models\Settings;
use App\Models\Shipping;
use App\Models\Tax;
use App\Models\User;
use App\Models\Variation;
use App\Models\Wallet;
use App\Modules\Checkout\DTO\CheckoutVerifyData;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class VerifyCheckoutAction
{
    public function __construct(private WalletService $walletService) {} // WalletService needs modularization

    /**
     * @return array{
     *     total_tax: float,
     *     shipping_charge: float,
     *     unavailable_products: array<int, int>,
     *     wallet_amount: float|int,
     *     wallet_currency: float|string,
     *     total_amount: float,
     *     order_total: float
     * }
     */
    public function execute(CheckoutVerifyData $data, User $authUser): array
    {
        $user = $authUser;

        $settings = Settings::getData();
        $minimumOrderAmount = (float) ($settings->options['minimumOrderAmount'] ?? 0);

        // 1. Hitung subtotal produk dari database
        $productsInCart = $this->calculateProductsSubtotal($data->products);
        $validProducts = $productsInCart['valid_products']; // Update data dengan produk yang sudah divalidasi dan dihitung ulang
        $unavailableProducts = $productsInCart['unavailable_products'];
        $amount = (float) $productsInCart['total_amount'];

        $isFreeShippingEnabled = (bool) ($settings->options['freeShipping'] ?? false);
        $freeShippingAmount = (float) ($settings->options['freeShippingAmount'] ?? 0);
        $shippingCharge = ($isFreeShippingEnabled && $freeShippingAmount <= $amount)
            ? 0.0
            : $this->calculateShippingCharge($validProducts, $amount);

        $tax = $this->calculateTax($data->billing_address, $data->shipping_address, $amount, $shippingCharge);

        $total = (float) ($amount + $tax + $shippingCharge);

        if ($total < $minimumOrderAmount) {
            throw new BadRequestHttpException('Minimum order amount is '.$minimumOrderAmount);
        }

        /** @var Wallet|null $wallet */
        $wallet = $user->wallet;
        $walletPoints = $wallet ? (int) $wallet->available_points : 0;

        return [
            'total_tax' => $tax,
            'shipping_charge' => $shippingCharge,
            'unavailable_products' => $unavailableProducts,
            'wallet_amount' => $walletPoints,
            'wallet_currency' => $this->walletService->walletPointsToCurrency($walletPoints),
            'total_amount' => $amount, // Tambahkan total amount yang dihitung server
            'order_total' => $total, // Tambahkan order total yang dihitung server
        ];
    }

    /**
     * @param  array<int, array{product_id: int, order_quantity: int, variation_option_id?: int|null, unit_price?: numeric, subtotal?: numeric}>  $productsInput
     * @return array{
     *     total_amount: float,
     *     unavailable_products: array<int, int>,
     *     valid_products: array<int, array<string, mixed>>
     * }
     */
    private function calculateProductsSubtotal(array $productsInput): array
    {
        $productIds = array_column($productsInput, 'product_id');
        $variationIds = array_filter(array_column($productsInput, 'variation_option_id'));

        /** @var Collection<int|string, Product> $productsById */
        $productsById = Product::whereIn('id', $productIds)->get()->keyBy('id');
        /** @var Collection<int|string, Variation> $variationsById */
        $variationsById = ! empty($variationIds)
            ? Variation::whereIn('id', $variationIds)->get()->keyBy('id')
            : collect();

        $totalAmount = 0.0;
        $unavailableProducts = [];
        $validProducts = [];

        foreach ($productsInput as $item) {
            $productId = (int) $item['product_id'];
            $variationId = isset($item['variation_option_id']) ? (int) $item['variation_option_id'] : null;
            $quantity = (int) $item['order_quantity'];

            $isUnavailable = false;
            $unitPrice = 0.0;
            $subtotal = 0.0;

            $productModel = $productsById->get($productId);
            if (! $productModel) {
                $isUnavailable = true;
            } else {
                $unitPrice = (float) ($productModel->sale_price ?? $productModel->price);
                if ($variationId) {
                    $variation = $variationsById->get($variationId);
                    if (! $variation || $variation->product_id !== $productId) {
                        $isUnavailable = true;
                    } else {
                        $unitPrice = (float) ($variation->sale_price ?? $variation->price);
                        if ($quantity > (int) $variation->quantity) {
                            $isUnavailable = true;
                        }
                    }
                } elseif ($quantity > (int) $productModel->quantity) {
                    $isUnavailable = true;
                }
            }

            if ($isUnavailable) {
                $unavailableProducts[] = $productId;
            } else {
                $subtotal = $unitPrice * $quantity;
                $totalAmount += $subtotal;
                $validProducts[] = [
                    'product_id' => $productId,
                    'variation_option_id' => $variationId,
                    'order_quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }
        }

        return [
            'total_amount' => $totalAmount,
            'unavailable_products' => $unavailableProducts,
            'valid_products' => $validProducts,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @param  array<int, int>  $unavailableProducts
     */
    public function getOrderAmount(array $products, array $unavailableProducts): float
    {
        // Fungsi ini tidak lagi dibutuhkan karena perhitungan dilakukan di calculateProductsSubtotal
        // Namun, jika masih ada yang memanggil, kita bisa sesuaikan.
        // Untuk sekarang, kita akan mengembalikannya menjadi simpel atau hapus jika tidak ada panggilan lain.
        // Jika getOrderAmount masih dipanggil, itu berarti ada bagian kode yang belum diupdate.
        return 0.0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, int>
     */
    public function checkStock(array $products): array
    {
        // Fungsi ini tidak lagi dibutuhkan karena stock check dilakukan di calculateProductsSubtotal
        return [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     */
    public function calculateShippingCharge(array $products, float $amount): float
    {
        $orderedProducts = $products;
        $physicalProductIds = Product::whereIn('id', Arr::pluck($orderedProducts, 'product_id'))
            ->where('is_digital', false)
            ->pluck('id')
            ->toArray();

        if (empty($physicalProductIds)) {
            return 0;
        }

        $settings = Settings::getData();
        $shippingClassId = $settings->options['shippingClass'] ?? null;

        if ($shippingClassId) {
            /** @var Shipping|null $shippingClass */
            $shippingClass = Shipping::find($shippingClassId);
            if ($shippingClass) {
                return $this->getShippingCharge($shippingClass, $amount);
            }
        }

        return $this->calculateShippingChargeByProduct($products);
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     */
    private function calculateShippingChargeByProduct(array $products): float
    {
        $productIds = array_column($products, 'product_id');
        $productSubtotals = array_column($products, 'subtotal', 'product_id');
        $products = Product::with('shipping')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $total = 0;
        foreach ($products as $productId => $product) {
            $subtotal = $productSubtotals[$productId] ?? 0;
            if ($product->shipping instanceof Shipping) {
                $subtotalFloat = is_numeric($subtotal) ? (float) $subtotal : 0.0;
                $total += $this->getShippingCharge($product->shipping, $subtotalFloat);
            }
        }

        return $total;
    }

    private function getShippingCharge(Shipping $shipping, float $amount): float
    {
        return match ($shipping->type) {
            'fixed' => (float) $shipping->amount,
            'percentage' => ($shipping->amount * $amount) / 100,
            default => 0,
        };
    }

    /**
     * @param  array<string, mixed>|null  $billingAddress
     * @param  array<string, mixed>|null  $shippingAddress
     */
    public function calculateTax(?array $billingAddress, ?array $shippingAddress, float $amount, float $shippingCharge): float
    {
        $taxClass = $this->getTaxClass($billingAddress, $shippingAddress);
        if (! $taxClass) {
            return 0;
        }

        return ($amount * $taxClass->rate) / 100;
    }

    /**
     * @param  array<string, mixed>|null  $billingAddress
     * @param  array<string, mixed>|null  $shippingAddress
     */
    private function getTaxClass(?array $billingAddress, ?array $shippingAddress): ?Tax
    {
        $address = $shippingAddress ?? $billingAddress; // Prioritaskan shipping address
        if (! $address) {
            // Jika tidak ada alamat, cari pajak global atau default
            return Tax::where('is_global', true)->orderBy('priority', 'desc')->first();
        }

        $query = Tax::query();

        // Membangun kondisi pencarian dari yang paling spesifik ke paling umum
        // Ini adalah contoh implementasi. Logika prioritas bisa lebih kompleks.
        $query->where(function ($q) use ($address) {
            $q->where(function ($q) use ($address) { // Pencarian City
                if (isset($address['city']) && ! empty($address['city'])) {
                    $q->where('city', $address['city']);
                    if (isset($address['state']) && ! empty($address['state'])) {
                        $q->where('state', $address['state']);
                    }
                    if (isset($address['country']) && ! empty($address['country'])) {
                        $q->where('country', $address['country']);
                    }
                } else {
                    $q->whereNull('city');
                }
            });
            $q->orWhere(function ($q) use ($address) { // Pencarian State
                if (isset($address['state']) && ! empty($address['state']) && (! isset($address['city']) || empty($address['city']))) {
                    $q->where('state', $address['state']);
                    if (isset($address['country']) && ! empty($address['country'])) {
                        $q->where('country', $address['country']);
                    }
                    $q->whereNull('city'); // Pastikan tidak overlap dengan city specific
                } else {
                    $q->whereNull('state')->whereNull('city'); // Pastikan tidak overlap
                }
            });
            $q->orWhere(function ($q) use ($address) { // Pencarian Country
                if (isset($address['country']) && ! empty($address['country']) && (! isset($address['state']) || empty($address['state'])) && (! isset($address['city']) || empty($address['city']))) {
                    $q->where('country', $address['country']);
                    $q->whereNull('state')->whereNull('city'); // Pastikan tidak overlap
                } else {
                    $q->whereNull('country')->whereNull('state')->whereNull('city'); // Fallback (global)
                }
            });
            $q->orWhere('is_global', true); // Selalu sertakan global sebagai opsi
        });

        // Urutkan berdasarkan prioritas dan spesifisitas
        return $query->orderBy('priority', 'desc')
            ->orderByRaw('CASE WHEN city IS NOT NULL THEN 4 WHEN state IS NOT NULL THEN 3 WHEN country IS NOT NULL THEN 2 WHEN is_global = 1 THEN 1 ELSE 0 END DESC')
            ->first();
    }
}
