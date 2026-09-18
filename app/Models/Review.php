<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'reviews';

    protected $fillable = [
        'rating', 'comment', 'photos', 'product_id', 'user_id', 'order_id',
        'shop_id', 'variation_option_id',
    ];

    protected $casts = [
        'photos' => 'json',
        'rating' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return MorphMany<Feedback, Review>
     */
    public function feedbacks(): MorphMany
    {
        return $this->morphMany(Feedback::class, 'model');
    }

    public function abusive_reports(): MorphMany
    {
        return $this->morphMany(AbusiveReport::class, 'model');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}
