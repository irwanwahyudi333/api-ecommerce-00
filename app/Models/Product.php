<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\Product\Exceptions\InventoryException;
use App\Modules\Shop\Events\ProductLowStock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Type|null $type
 * @property Shop|null $shop
 * @property Author|null $author
 * @property Manufacturer|null $manufacturer
 * @property string $language
 * @property string $product_type
 * @property mixed $translated_languages
 * @property mixed $metas
 * @property mixed $digital_file
 * @property mixed $variation_options
 * @property Collection<int, AttributeValue> $variations
 * @property Collection<int, Product> $related_products
 * @property string|null $description
 * @property bool $in_stock
 * @property bool $is_taxable
 * @property bool $is_digital
 * @property bool $is_external
 * @property string|null $external_product_url
 * @property string|null $external_product_button_text
 * @property float $sale_price
 * @property float|null $max_price
 * @property float|null $min_price
 * @property mixed $ratings
 * @property int $total_reviews
 * @property int $rating_count
 * @property mixed $my_review
 * @property bool $in_wishlist
 * @property string|null $sku
 * @property mixed $gallery
 * @property mixed $image
 * @property mixed $video
 * @property string $status
 * @property float|null $height
 * @property float|null $length
 * @property float|null $width
 * @property float $price
 * @property int $quantity
 * @property string|null $unit
 * @property bool $in_flash_sale
 * @property bool|null $is_rental
 *
 * @method void setMeta(string $key, mixed $value = null)
 * @method \Illuminate\Database\Eloquent\Relations\HasMany metas()
 */
class Product extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(fn (Product $product) => $product->wasLowStock() && event(new ProductLowStock($product)));
    }

    protected $table = 'products';

    protected $fillable = [
        'name', 'slug', 'price', 'sale_price', 'max_price', 'min_price',
        'description', 'sku', 'quantity', 'unit', 'language',
        'image', 'gallery', 'video', 'status', 'product_type',
        'is_rental', 'is_digital', 'is_external', 'in_stock', 'is_taxable',
        'shop_id', 'type_id', 'author_id', 'manufacturer_id', 'shipping_class_id',
        'sold_quantity', 'visibility', 'reserved_quantity', 'low_stock_threshold',
        'currency_code',
    ];

    protected $casts = [
        'image' => 'json',
        'gallery' => 'json',
        'video' => 'json',
        'is_rental' => 'boolean',
        'is_digital' => 'boolean',
        'is_external' => 'boolean',
        'in_stock' => 'boolean',
        'is_taxable' => 'boolean',
        'in_flash_sale' => 'boolean',
        'reserved_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'available_quantity' => 'integer',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function variation_options(): HasMany
    {
        return $this->hasMany(Variation::class, 'product_id');
    }

    public function variations(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_product');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function digital_file(): MorphOne
    {
        return $this->morphOne(DigitalFile::class, 'fileable');
    }

    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'bookable');
    }

    public function dropoff_locations(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'dropoff_location_product', 'product_id', 'resource_id');
    }

    public function pickup_locations(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'pickup_location_product', 'product_id', 'resource_id');
    }

    public function deposits(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'deposit_product', 'product_id', 'resource_id');
    }

    public function persons(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'person_product', 'product_id', 'resource_id');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'feature_product', 'product_id', 'resource_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_product')
            ->withPivot('order_quantity', 'unit_price', 'subtotal', 'variation_option_id')
            ->withTimestamps();
    }

    public function shipping(): BelongsTo
    {
        return $this->belongsTo(Shipping::class, 'shipping_class_id');
    }

    public function isLowStock(): bool
    {
        return $this->available_quantity <= $this->low_stock_threshold;
    }

    public function wasLowStock(): bool
    {
        return $this->available_quantity <= $this->low_stock_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->available_quantity <= 0;
    }

    public function reserveStock(int $quantityToReserve): void
    {
        DB::transaction(function () use ($quantityToReserve) {
            $this->refresh(); // Ambil data terbaru dari DB
            if ($this->available_quantity < $quantityToReserve) {
                throw new InventoryException('Not enough stock to reserve '.$this->name.'. Available: '.$this->available_quantity.', Requested: '.$quantityToReserve);
            }
            $this->increment('reserved_quantity', $quantityToReserve);
            $this->refresh();
        });
    }

    public function releaseStock(int $quantityToRelease): void
    {
        DB::transaction(function () use ($quantityToRelease) {
            $this->refresh();
            if ($this->reserved_quantity < $quantityToRelease) {
                throw new InventoryException('Not enough reserved stock to release for '.$this->name.'. Reserved: '.$this->reserved_quantity.', Requested: '.$quantityToRelease);
            }
            $this->decrement('reserved_quantity', $quantityToRelease);
            $this->refresh();
        });
    }

    public function flashSales(): BelongsToMany
    {
        return $this->belongsToMany(FlashSale::class, 'flash_sale_products')->withPivot('flash_sale_id', 'product_id');
    }

    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        // Customize the data array for searching
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'max_price' => $this->max_price,
            'min_price' => $this->min_price,
            'in_stock' => $this->in_stock,
            'shop_id' => $this->shop_id,
            'type_id' => $this->type_id,
            'author_id' => $this->author_id,
            'manufacturer_id' => $this->manufacturer_id,
            'categories' => $this->categories->pluck('name')->toArray(),
            'tags' => $this->tags->pluck('name')->toArray(),
            'status' => $this->status,
            'product_type' => $this->product_type,
        ];
    }
}
