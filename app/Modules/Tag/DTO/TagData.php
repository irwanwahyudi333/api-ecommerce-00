<?php

declare(strict_types=1);

namespace App\Modules\Tag\DTO;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagData
{
    /**
     * @param  array<mixed>|null  $image
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $slug,
        public readonly ?int $typeId,
        public readonly ?string $icon,
        public readonly ?array $image,
        public readonly ?string $details,
        public readonly string $language,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $nameRaw = $request->input('name', '');
        $name = is_string($nameRaw) ? $nameRaw : '';

        $typeIdRaw = $request->input('type_id');
        $iconRaw = $request->input('icon');
        $detailsRaw = $request->input('details');
        $langRaw = $request->input('language', config('shop.default_language', 'id'));

        $defaultLangRaw = config('shop.default_language', 'id');
        $defaultLang = is_string($defaultLangRaw) ? $defaultLangRaw : 'id';

        return new self(
            name: $name,
            slug: Str::slug($name),
            typeId: is_numeric($typeIdRaw) ? (int) $typeIdRaw : null,
            icon: is_string($iconRaw) ? $iconRaw : null,
            image: is_array($request->input('image')) ? $request->input('image') : null,
            details: is_string($detailsRaw) ? $detailsRaw : null,
            language: is_string($langRaw) ? $langRaw : $defaultLang,
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
            'type_id' => $this->typeId,
            'icon' => $this->icon,
            'image' => $this->image,
            'details' => $this->details,
            'language' => $this->language,
        ];
    }
}
