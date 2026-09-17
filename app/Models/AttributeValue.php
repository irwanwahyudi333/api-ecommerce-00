<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $value
 * @property int $attribute_id
 * @property string $slug
 * @property string|null $meta
 * @property string $language
 * @property array $translated_languages
 * @property \App\Models\Attribute|null $attribute
 */
class AttributeValue extends Model
{
    use HasFactory;

    protected $table = 'attribute_values';

    protected $guarded = [];

    protected $appends = ['translated_languages'];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->value);
                $count = static::where('slug', $model->slug)->where('language', $model->language)->count();
                if ($count > 0) {
                    $model->slug = $model->slug.'-'.($count + 1);
                }
            }
        });
    }

    public function getTranslatedLanguagesAttribute()
    {
        return static::where('slug', $this->slug)->pluck('language')->toArray();
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
