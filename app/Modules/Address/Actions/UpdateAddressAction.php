<?php

declare(strict_types=1);

namespace App\Modules\Address\Actions;

use App\Models\Address;
use App\Modules\Address\DTO\AddressData;
use Illuminate\Support\Facades\DB;

final class UpdateAddressAction
{
    public function execute(Address $address, AddressData $data): Address
    {
        /** @var Address $result */
        $result = DB::transaction(function () use ($address, $data) {
            // Jika alamat di-set sebagai default, unset default yang lain
            if ($data->default) {
                Address::where('customer_id', $address->customer_id)
                    ->where('id', '!=', $address->id)
                    ->update(['default' => false]);
            }

            $address->update($data->toArray());

            return $address->refresh();
        });

        return $result;
    }
}
