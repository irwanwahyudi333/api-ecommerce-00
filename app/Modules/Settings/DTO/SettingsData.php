<?php

declare(strict_types=1);

namespace App\Modules\Settings\DTO;

use Illuminate\Http\Request;

class SettingsData
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly array $options,
        public readonly string $language,
    ) {}

    public static function fromRequest(Request $request): self
    {
        /** @var array<string, mixed> $options */
        $options = $request->input('options') ?? [];
        $languageInput = $request->input('language', config('shop.default_language', 'id'));

        return new self(
            options: $options,
            language: is_string($languageInput) ? $languageInput : '',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'options' => $this->options,
            'language' => $this->language,
        ];
    }
}
