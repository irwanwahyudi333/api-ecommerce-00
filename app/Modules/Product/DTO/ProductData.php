<?php

declare(strict_types=1);

namespace App\Modules\Product\DTO;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductData
{
    /**
     * @param  array<array-key, mixed>|null  $image
     * @param  array<array-key, mixed>|null  $gallery
     * @param  array<array-key, mixed>|null  $video
     * @param  array<array-key, mixed>|null  $categories
     * @param  array<array-key, mixed>|null  $tags
     * @param  array<array-key, mixed>|null  $dropoff_locations
     * @param  array<array-key, mixed>|null  $pickup_locations
     * @param  array<array-key, mixed>|null  $persons
     * @param  array<array-key, mixed>|null  $features
     * @param  array<array-key, mixed>|null  $deposits
     * @param  array<array-key, mixed>|null  $metas
     * @param  array<array-key, mixed>|null  $variations
     * @param  array<array-key, mixed>|null  $variation_options
     * @param  array<array-key, mixed>|null  $digital_file
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?float $price,
        public readonly ?float $sale_price,
        public readonly ?float $max_price,
        public readonly ?float $min_price,
        public readonly ?int $type_id,
        public readonly ?int $shop_id,
        public readonly ?int $author_id,
        public readonly ?int $manufacturer_id,
        public readonly ?string $language,
        public readonly ?string $product_type,
        public readonly ?int $quantity,
        public readonly ?string $unit,
        public readonly ?bool $is_digital,
        public readonly ?bool $is_external,
        public readonly ?string $external_product_url,
        public readonly ?string $external_product_button_text,
        public readonly ?string $description,
        public readonly ?string $sku,
        public readonly ?array $image,
        public readonly ?array $gallery,
        public readonly ?array $video,
        public readonly ?string $status,
        public readonly ?string $height,
        public readonly ?string $length,
        public readonly ?string $width,
        public readonly ?bool $in_stock,
        public readonly ?bool $is_taxable,
        public readonly ?int $sold_quantity,
        public readonly ?string $visibility,
        public readonly ?array $categories,
        public readonly ?array $tags,
        public readonly ?array $dropoff_locations,
        public readonly ?array $pickup_locations,
        public readonly ?array $persons,
        public readonly ?array $features,
        public readonly ?array $deposits,
        public readonly ?array $metas,
        public readonly ?array $variations,
        public readonly ?array $variation_options,
        public readonly ?array $digital_file,
        public readonly ?bool $inform_purchased_customer,
        public readonly ?string $product_update_message,
        public readonly ?bool $is_rental,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    private static function getString(array $data, string $key): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function getFloat(array $data, string $key): ?float
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (float) $data[$key] : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function getInt(array $data, string $key): ?int
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (int) $data[$key] : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function getBool(array $data, string $key): ?bool
    {
        return isset($data[$key]) ? (bool) $data[$key] : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>|null
     */
    private static function getArray(array $data, string $key): ?array
    {
        return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: self::getString($data, 'name'),
            slug: self::getString($data, 'slug'),
            price: self::getFloat($data, 'price'),
            sale_price: self::getFloat($data, 'sale_price'),
            max_price: self::getFloat($data, 'max_price'),
            min_price: self::getFloat($data, 'min_price'),
            type_id: self::getInt($data, 'type_id'),
            shop_id: self::getInt($data, 'shop_id'),
            author_id: self::getInt($data, 'author_id'),
            manufacturer_id: self::getInt($data, 'manufacturer_id'),
            language: self::getString($data, 'language') ?? (is_string(config('shop.default_language', 'id')) ? config('shop.default_language', 'id') : 'id'),
            product_type: self::getString($data, 'product_type'),
            quantity: self::getInt($data, 'quantity'),
            unit: self::getString($data, 'unit'),
            is_digital: self::getBool($data, 'is_digital'),
            is_external: self::getBool($data, 'is_external'),
            external_product_url: self::getString($data, 'external_product_url'),
            external_product_button_text: self::getString($data, 'external_product_button_text'),
            description: self::getString($data, 'description'),
            sku: self::getString($data, 'sku'),
            image: self::getArray($data, 'image'),
            gallery: self::getArray($data, 'gallery'),
            video: self::getArray($data, 'video'),
            status: self::getString($data, 'status'),
            height: self::getString($data, 'height'),
            length: self::getString($data, 'length'),
            width: self::getString($data, 'width'),
            in_stock: self::getBool($data, 'in_stock'),
            is_taxable: self::getBool($data, 'is_taxable'),
            sold_quantity: self::getInt($data, 'sold_quantity') ?? 0,
            visibility: self::getString($data, 'visibility') ?? 'visible',
            categories: self::getArray($data, 'categories'),
            tags: self::getArray($data, 'tags'),
            dropoff_locations: self::getArray($data, 'dropoff_locations'),
            pickup_locations: self::getArray($data, 'pickup_locations'),
            persons: self::getArray($data, 'persons'),
            features: self::getArray($data, 'features'),
            deposits: self::getArray($data, 'deposits'),
            metas: self::getArray($data, 'metas'),
            variations: self::getArray($data, 'variations'),
            variation_options: self::getArray($data, 'variation_options'),
            digital_file: self::getArray($data, 'digital_file'),
            inform_purchased_customer: self::getBool($data, 'inform_purchased_customer') ?? false,
            product_update_message: self::getString($data, 'product_update_message'),
            is_rental: self::getBool($data, 'is_rental'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Validation rules for DTO creation
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug',
            'price' => 'required|numeric|min:0',
            'shop_id' => 'required|integer|exists:shops,id',
            'status' => 'required|in:publish,draft,private',
            'quantity' => 'required|integer|min:0',
            'language' => 'required|string|size:2',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return new self(
            name: self::getString($data, 'name'),
            slug: self::getString($data, 'slug'),
            price: self::getFloat($data, 'price'),
            sale_price: self::getFloat($data, 'sale_price'),
            max_price: self::getFloat($data, 'max_price'),
            min_price: self::getFloat($data, 'min_price'),
            type_id: self::getInt($data, 'type_id'),
            shop_id: self::getInt($data, 'shop_id'),
            author_id: self::getInt($data, 'author_id'),
            manufacturer_id: self::getInt($data, 'manufacturer_id'),
            language: self::getString($data, 'language') ?? (is_string(config('shop.default_language', 'id')) ? config('shop.default_language', 'id') : 'id'),
            product_type: self::getString($data, 'product_type'),
            quantity: self::getInt($data, 'quantity'),
            unit: self::getString($data, 'unit'),
            is_digital: self::getBool($data, 'is_digital'),
            is_external: self::getBool($data, 'is_external'),
            external_product_url: self::getString($data, 'external_product_url'),
            external_product_button_text: self::getString($data, 'external_product_button_text'),
            description: self::getString($data, 'description'),
            sku: self::getString($data, 'sku'),
            image: self::getArray($data, 'image'),
            gallery: self::getArray($data, 'gallery'),
            video: self::getArray($data, 'video'),
            status: self::getString($data, 'status'),
            height: self::getString($data, 'height'),
            length: self::getString($data, 'length'),
            width: self::getString($data, 'width'),
            in_stock: self::getBool($data, 'in_stock'),
            is_taxable: self::getBool($data, 'is_taxable'),
            sold_quantity: self::getInt($data, 'sold_quantity') ?? 0,
            visibility: self::getString($data, 'visibility') ?? 'visible',
            categories: self::getArray($data, 'categories'),
            tags: self::getArray($data, 'tags'),
            dropoff_locations: self::getArray($data, 'dropoff_locations'),
            pickup_locations: self::getArray($data, 'pickup_locations'),
            persons: self::getArray($data, 'persons'),
            features: self::getArray($data, 'features'),
            deposits: self::getArray($data, 'deposits'),
            metas: self::getArray($data, 'metas'),
            variations: self::getArray($data, 'variations'),
            variation_options: self::getArray($data, 'variation_options'),
            digital_file: self::getArray($data, 'digital_file'),
            inform_purchased_customer: self::getBool($data, 'inform_purchased_customer') ?? false,
            product_update_message: self::getString($data, 'product_update_message'),
            is_rental: self::getBool($data, 'is_rental'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'max_price' => $this->max_price,
            'min_price' => $this->min_price,
            'type_id' => $this->type_id,
            'shop_id' => $this->shop_id,
            'author_id' => $this->author_id,
            'manufacturer_id' => $this->manufacturer_id,
            'language' => $this->language,
            'product_type' => $this->product_type,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'is_digital' => $this->is_digital,
            'is_external' => $this->is_external,
            'external_product_url' => $this->external_product_url,
            'external_product_button_text' => $this->external_product_button_text,
            'description' => $this->description,
            'sku' => $this->sku,
            'image' => $this->image,
            'gallery' => $this->gallery,
            'video' => $this->video,
            'status' => $this->status,
            'height' => $this->height,
            'length' => $this->length,
            'width' => $this->width,
            'in_stock' => $this->in_stock,
            'is_taxable' => $this->is_taxable,
            'sold_quantity' => $this->sold_quantity,
            'visibility' => $this->visibility,
            'categories' => $this->categories,
            'tags' => $this->tags,
            'dropoff_locations' => $this->dropoff_locations,
            'pickup_locations' => $this->pickup_locations,
            'persons' => $this->persons,
            'features' => $this->features,
            'deposits' => $this->deposits,
            'metas' => $this->metas,
            'variations' => $this->variations,
            'variation_options' => $this->variation_options,
            'digital_file' => $this->digital_file,
            'inform_purchased_customer' => $this->inform_purchased_customer,
            'product_update_message' => $this->product_update_message,
            'is_rental' => $this->is_rental,
        ];
    }

    public function getShopId(): ?int
    {
        return $this->shop_id;
    }

    public function isDigital(): bool
    {
        return $this->is_digital === true;
    }

    public function isRental(): bool
    {
        return $this->is_rental === true;
    }
}
