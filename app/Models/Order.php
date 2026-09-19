<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $payment_gateway
 * @property string|null $altered_payment_gateway
 */
class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'tracking_number',
        'customer_id',
        'customer_type',
        'payment_status',
        'payment_intent',
        'payment_method',
        'payment_provider',
        'payment_note',
        'order_status',
        'delivery_time',
        'shipping_address_id',
        'shipping_address',
        'billing_address',
        'shipping_method_id',
        'currency',
        'conversion_rate',
        'coupon_id',
        'shop_id',
        'parent_id',
        'note',
        'tax',
        'discount',
        'subtotal',
        'total',
        'admin_revenue',
        'shop_revenue',
        'commission_rate',
        'language',
        'payment_intent_info',
        'wallet_point',
        'wallet_point_total',
        'is_guest',
        'cancelled_at',
        'shipping_cost',
        'paid_total',
        'customer_email',
        'customer_name',
        'customer_phone',
        'customer_country',
        'customer_city',
        'customer_state',
        'customer_zip',
        'product_id',
    ];

    protected $casts = [
        'shipping_address' => 'json',
        'billing_address' => 'json',
        'payment_intent_info' => 'json',
        'wallet_point' => 'boolean',
    ];

    protected $hidden = [
        'payment_intent_info',
        'customer_phone',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('order', fn ($q) => $q->orderBy('created_at', 'desc'));
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'order_product')
            ->withPivot('order_quantity', 'unit_price', 'subtotal', 'variation_option_id')
            ->withTimestamps();
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    /**
     * @return HasMany<Order>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Order::class, 'parent_id', 'id');
    }

    public function parent_order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'parent_id');
    }

    public function refund(): HasMany
    {
        return $this->hasMany(Refund::class, 'order_id');
    }

    public function wallet_point(): HasMany
    {
        return $this->hasMany(OrderWalletPoint::class, 'order_id');
    }

    public function payment_intent(): HasMany
    {
        return $this->hasMany(PaymentIntent::class, 'order_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'order_id');
    }
}
