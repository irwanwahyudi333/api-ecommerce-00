<?php

declare(strict_types=1);

namespace App\Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Khusus admin -> bedanya dengan UpdateUserAction: shop_id boleh diubah
 * karena sudah lolos AdminUpdateUserRequest::authorize() (Policy::update).
 */
final class AdminUpdateUserAction
{
    /**
     * @param  array{name?:?string,email?:?string,shop_id?:?int}  $validated
     */
    public function execute(User $user, array $validated): User
    {
        return DB::transaction(function () use ($user, $validated): User {
            $updateData = array_filter(
                [
                    'name' => $validated['name'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'shop_id' => array_key_exists('shop_id', $validated) ? $validated['shop_id'] : null,
                ],
                static fn ($value) => $value !== null
            );

            if ($updateData !== []) {
                $user->update($updateData);
            }

            $freshUser = $user->fresh(['profile', 'address', 'shops', 'managed_shop']);
            assert($freshUser instanceof User);

            return $freshUser;
        });
    }
}
