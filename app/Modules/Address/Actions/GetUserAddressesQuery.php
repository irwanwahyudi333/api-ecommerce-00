<?php

declare(strict_types=1);

namespace App\Modules\Address\Actions;

use App\Models\Address;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class GetUserAddressesQuery
{
    /**
     * @return LengthAwarePaginator<int, Address>
     */
    public function execute(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Address::query()
            ->where('customer_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }
}
