<?php

declare(strict_types=1);

namespace App\Modules\Language\DTO;

final class LanguageData
{
    public function __construct(
        public readonly string $language_name,
        public readonly string $language_code,
        public readonly string $flag,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            language_name: is_string($data['language_name'] ?? null) ? $data['language_name'] : '',
            language_code: is_string($data['language_code'] ?? null) ? $data['language_code'] : '',
            flag: is_string($data['flag'] ?? null) ? $data['flag'] : '',
        );
    }
}
