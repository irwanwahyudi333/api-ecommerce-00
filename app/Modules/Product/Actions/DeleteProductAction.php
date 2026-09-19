<?php

declare(strict_types=1);

namespace App\Modules\Product\Actions;

use App\Models\Product;
use App\Models\Variation;
use Illuminate\Support\Facades\DB;

class DeleteProductAction
{
    public function execute(Product $product): void
    {
        DB::transaction(function () use ($product) {
            // Detach relasi many-to-many sebelum soft delete
            $product->categories()->detach();
            $product->tags()->detach();
            $product->variations()->detach();
            $product->dropoff_locations()->detach();
            $product->pickup_locations()->detach();
            $product->persons()->detach();
            $product->features()->detach();
            $product->deposits()->detach();

            // Hapus digital file terkait jika ada
            if ($product->digital_file) {
                $product->digital_file()->delete();
            }

            // Hapus variation options (dan digital file masing-masing)
            $product->variation_options()->each(function ($variation) {
                /** @var Variation $variation */
                if ($variation->digital_file) {
                    $variation->digital_file()->delete();
                }
                $variation->delete();
            });

            // Hapus availability jika produk rental
            $product->availabilities()->delete();

            // Hapus metas
            $product->metas()->delete();

            // Soft delete produk
            $product->delete();
        });
    }
}
