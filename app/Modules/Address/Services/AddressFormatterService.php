<?php

declare(strict_types=1);

namespace App\Modules\Address\Services;

final class AddressFormatterService
{
    /**
     * @param  array<string, mixed>|string|null  $address
     */
    public function format(array|string|null $address): string
    {
        if (empty($address)) {
            return '';
        }

        if (is_array($address)) {
            $strings = [];
            foreach ($address as $value) {
                if (! empty($value) && (is_scalar($value) || $value instanceof \Stringable)) {
                    $strings[] = (string) $value;
                }
            }

            return implode(', ', $strings);
        }

        return (string) $address;
    }
}
