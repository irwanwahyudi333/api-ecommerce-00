<?php

declare(strict_types=1);

namespace App\Modules\BecameSeller\DTO;

class CommissionData
{
    public function __construct(
        public readonly float $min_balance,
        public readonly float $max_balance,
        public readonly float $commission,
        public readonly string $level,
        public readonly string $sub_level,
        public readonly ?string $language,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, ?string $language = null): self
    {
        $min_balance = is_scalar($data['min_balance'] ?? null) ? (float) $data['min_balance'] : 0.0;
        $max_balance = is_scalar($data['max_balance'] ?? null) ? (float) $data['max_balance'] : 0.0;
        $commission = is_scalar($data['commission'] ?? null) ? (float) $data['commission'] : 0.0;
        $level = is_scalar($data['level'] ?? null) ? (string) $data['level'] : '';
        $sub_level = is_scalar($data['sub_level'] ?? null) ? (string) $data['sub_level'] : '';

        return new self(
            min_balance: $min_balance,
            max_balance: $max_balance,
            commission: $commission,
            level: $level,
            sub_level: $sub_level,
            language: $language,
        );
    }
}
