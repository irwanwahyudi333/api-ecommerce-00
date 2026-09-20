<?php

namespace App\Enums;

enum RefundPolicyTarget: string
{
    case VENDOR = 'vendor';
    case CUSTOMER = 'customer';

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
            self::VENDOR => 'Vendor',
            self::CUSTOMER => 'Customer',
        };
    }
}
