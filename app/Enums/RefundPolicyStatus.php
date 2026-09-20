<?php

namespace App\Enums;

enum RefundPolicyStatus: string
{
    case APPROVED = 'approved';
    case PENDING = 'pending';

    /**
     * Get all values for database enum
     *
     * @return list<string>
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Approved',
            self::PENDING => 'Pending',
        };
    }
}
