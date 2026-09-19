<?php

namespace App\Modules\Product\Actions;

use App\Models\DigitalFile;
use App\Models\Product;
use App\Models\Variation;
use App\Modules\Product\DTO\ProductData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProductAction
{
    /**
     * @param  object  $settings
     */
    public function execute(ProductData $data, $settings): Product
    {
        /** @var Product $result */
        $result = DB::transaction(function () use ($data, $settings) {
            $attributes = $this->prepareAttributes($data);
            $attributes['status'] = $this->determineStatus($data, $settings);

            /** @var Product $product */
            $product = Product::create($attributes);

            if ($data->product_type === 'simple') {
                $product->update([
                    'min_price' => $product->price,
                    'max_price' => $product->price,
                ]);
            }

            // Amankan dari looping jika metas bukan array
            if (is_array($data->metas)) {
                foreach ($data->metas as $meta) {
                    if (is_array($meta) && isset($meta['key']) && is_string($meta['key'])) {
                        $product->setMeta($meta['key'], $meta['value'] ?? null);
                    }
                }
            }

            $this->syncRelations($product, $data);

            if (is_array($data->variation_options) && isset($data->variation_options['upsert']) && is_array($data->variation_options['upsert'])) {
                $this->handleVariationOptions($product, $data->variation_options['upsert']);
            }

            if ($data->is_digital && is_array($data->digital_file)) {
                /** @var array<string, mixed> $digitalFileArr */
                $digitalFileArr = $data->digital_file;
                $product->digital_file()->create($digitalFileArr);
            }

            /** @var Product $freshProduct */
            $freshProduct = $product->fresh();

            return $freshProduct;
        });

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareAttributes(ProductData $data): array
    {
        // Untuk CREATE, kita mengambil semua properti dari DTO direct ke array
        $attributes = [
            'name' => $data->name,
            'price' => $data->price,
            'sale_price' => $data->sale_price,
            'max_price' => $data->max_price,
            'min_price' => $data->min_price,
            'type_id' => $data->type_id,
            'shop_id' => $data->shop_id,
            'author_id' => $data->author_id,
            'manufacturer_id' => $data->manufacturer_id,
            'language' => $data->language,
            'product_type' => $data->product_type,
            'quantity' => $data->quantity,
            'unit' => $data->unit,
            'is_digital' => $data->is_digital,
            'is_external' => $data->is_external,
            'external_product_url' => $data->external_product_url,
            'external_product_button_text' => $data->external_product_button_text,
            'description' => $data->description,
            'sku' => $data->sku,
            'image' => $data->image,
            'gallery' => $data->gallery,
            'video' => $data->video,
            'height' => $data->height,
            'length' => $data->length,
            'width' => $data->width,
            'in_stock' => $data->in_stock,
            'is_taxable' => $data->is_taxable,
            'sold_quantity' => $data->sold_quantity,
            'visibility' => $data->visibility,
            'is_rental' => $data->is_rental,
        ];

        // Memanfaatkan helper fungsi generateUniqueSlug yang kamu buat
        $nameForSlug = $data->slug ?: (string) $data->name;
        /** @var string $slug */
        $slug = function_exists('generateUniqueSlug') ? generateUniqueSlug(Product::class, $nameForSlug, $data->language) : Str::slug($nameForSlug);
        $attributes['slug'] = $slug;

        return $attributes;
    }

    /**
     * @param  object  $settings
     */
    private function determineStatus(ProductData $data, $settings): string
    {
        $needsReview = false;
        if (property_exists($settings, 'options') && is_array($settings->options)) {
            $needsReview = $settings->options['isProductReview'] ?? false;
        }
        if ($needsReview) {
            return $data->status === 'draft' ? 'draft' : 'under_review';
        }

        return $data->status ?? 'publish';
    }

    private function syncRelations(Product $product, ProductData $data): void
    {
        if (is_array($data->categories)) {
            $product->categories()->sync($data->categories);
        }
        if (is_array($data->tags)) {
            $product->tags()->sync($data->tags);
        }
        if (is_array($data->dropoff_locations)) {
            $product->dropoff_locations()->sync($data->dropoff_locations);
        }
        if (is_array($data->pickup_locations)) {
            $product->pickup_locations()->sync($data->pickup_locations);
        }
        if (is_array($data->persons)) {
            $product->persons()->sync($data->persons);
        }
        if (is_array($data->features)) {
            $product->features()->sync($data->features);
        }
        if (is_array($data->deposits)) {
            $product->deposits()->sync($data->deposits);
        }
        if (is_array($data->variations)) {
            $product->variations()->sync($data->variations);
        }
    }

    /**
     * @param  array<array-key, mixed>  $variations
     */
    private function handleVariationOptions(Product $product, array $variations): void
    {
        foreach ($variations as $variationData) {
            if (! is_array($variationData)) {
                continue;
            }
            /** @var array<string, mixed> $variationArr */
            $variationArr = $variationData;
            /** @var Variation $variation */
            $variation = $product->variation_options()->create($variationArr);
            if (($variationArr['is_digital'] ?? false) && isset($variationArr['digital_file']) && is_array($variationArr['digital_file'])) {
                /** @var array<string, mixed> $vDigitalFile */
                $vDigitalFile = $variationArr['digital_file'];
                /** @var DigitalFile $digitalFile */
                $digitalFile = $variation->digital_file()->create($vDigitalFile);
                $variation->update(['digital_file_tracker' => $digitalFile->id]);
            }
        }
    }
}
