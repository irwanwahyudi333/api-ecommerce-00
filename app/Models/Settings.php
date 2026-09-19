<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @phpstan-type SettingsOptions array{
 *     reviewSystem?: array{
 *         value?: string
 *     },
 *     currency?: string,
 *     currencyOptions?: array{
 *         formation?: string
 *     },
 *     minimumOrderAmount?: float|int,
 *     freeShipping?: bool,
 *     freeShippingAmount?: float|int,
 *     shippingClass?: int|string,
 *     guestCheckout?: bool,
 *     paymentGateway?: array<int, array{name?: string}>
 * }
 *
 * @property SettingsOptions $options
 */
class Settings extends Model
{
    use HasFactory;

    protected $table = 'settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'language',
        'options',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'json',
        ];
    }

    private const CACHE_TTL_SECONDS = 3600;

    /**
     * PERFORMANCE:
     * Simpan hanya atribut model ke Redis, bukan object Eloquent.
     * Ini menghindari masalah __PHP_Incomplete_Class saat upgrade
     * Laravel/PHP atau perubahan struktur model.
     */
    public static function getData(?string $language = null): self
    {
        $lang = $language ?? config('shop.default_language', 'id');

        $attributes = Cache::remember(
            "settings:{$lang}",
            self::CACHE_TTL_SECONDS,
            function () use ($lang): array {
                $settings = static::where('language', $lang)->first();

                if (! $settings) {
                    $settings = static::where('language', 'id')->first();
                }

                return $settings?->getAttributes() ?? [
                    'language' => $lang,
                    'options' => [],
                ];
            }
        );

        $model = new self;
        $model->forceFill($attributes);

        // Anggap sebagai model yang berasal dari database
        if (isset($attributes['id'])) {
            $model->exists = true;
            $model->syncOriginal();
        }

        return $model;
    }

    protected static function booted(): void
    {
        static::saved(function (self $settings): void {
            Cache::forget("settings:{$settings->language}");
        });

        static::deleted(function (self $settings): void {
            Cache::forget("settings:{$settings->language}");
        });
    }
}
