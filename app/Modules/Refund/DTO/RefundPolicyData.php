<?php

declare(strict_types=1);

namespace App\Modules\Refund\DTO;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RefundPolicyData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $slug,
        public readonly string $target,
        public readonly string $status,
        public readonly ?string $description,
        public readonly ?int $shopId,
        public readonly string $language,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $title = is_string($request->input('title')) ? $request->input('title') : '';
        $target = is_string($request->input('target')) ? $request->input('target') : '';
        $status = is_string($request->input('status')) ? $request->input('status') : '';
        $description = is_string($request->input('description')) ? $request->input('description') : null;
        $shopId = is_numeric($request->input('shop_id')) ? (int) $request->input('shop_id') : null;
        $defaultLang = config('shop.default_language', 'id');
        $language = is_string($request->input('language')) ? $request->input('language') : (is_string($defaultLang) ? $defaultLang : 'id');

        return new self(
            title: $title,
            slug: Str::slug($title),
            target: $target,
            status: $status,
            description: $description,
            shopId: $shopId,
            language: $language,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'target' => $this->target,
            'status' => $this->status,
            'description' => $this->description,
            'shop_id' => $this->shopId,
            'language' => $this->language,
        ];
    }
}
