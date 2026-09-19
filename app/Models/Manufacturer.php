<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $language
 * @property int $products_count
 * @property bool $is_approved
 * @property string|null $description
 * @property string|null $website
 * @property array|null $socials
 * @property array|null $image
 * @property array|null $cover_image
 * @property array $translated_languages
 * @property int|null $shop_id
 * @property Type|null $type
 */
class Manufacturer extends Model
{
    use HasFactory;

    protected $table = 'manufacturers';

    protected $guarded = [];

    protected $casts = [
        'image' => 'json',
        'cover_image' => 'json',
        'socials' => 'json',
        'is_approved' => 'boolean',
    ];

    protected $appends = ['products_count', 'translated_languages'];

    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    public function getTranslatedLanguagesAttribute(): array
    {
        return static::where('slug', $this->slug)->pluck('language')->toArray();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'manufacturer_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id');
    }
}
