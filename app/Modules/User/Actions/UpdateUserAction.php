<?php

declare(strict_types=1);

namespace App\Modules\User\Actions;

use App\Models\Address;
use App\Models\Profile;
use App\Models\User;
use App\Modules\User\DTO\UpdateUserData;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdateUserAction
{
    public function execute(User $user, UpdateUserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            if ($data->address) {
                foreach ($data->address as $address) {
                    if (! is_array($address)) {
                        continue;
                    }
                    /** @var array<string, mixed> $payload */
                    $payload = Arr::only($address, [
                        'title', 'type', 'street_address', 'city', 'state',
                        'zip', 'country', 'phone', 'is_default',
                    ]);

                    if (isset($address['id'])) {
                        // where('customer_id', $user->id) WAJIB ada -> mencegah IDOR
                        // (user A tidak bisa update alamat milik user B walau tahu ID-nya).
                        Address::query()
                            ->where('id', $address['id'])
                            ->where('customer_id', $user->id)
                            ->update($payload);
                    } else {
                        $user->address()->create($payload);
                    }
                }
            }

            if ($data->profile && is_array($data->profile)) {
                /** @var array<string, mixed> $payload */
                $payload = Arr::only($data->profile, ['bio', 'avatar', 'contact', 'gender', 'birth_date']);

                if (isset($data->profile['id'])) {
                    Profile::query()
                        ->where('id', $data->profile['id'])
                        ->where('customer_id', $user->id)
                        ->update($payload);
                } else {
                    $user->profile()->create($payload);
                }
            }

            $updateData = array_filter([
                'name' => $data->name,
                'email' => $data->email,
            ], static fn ($value) => $value !== null);

            if ($updateData !== []) {
                $user->update($updateData);
            }

            $freshUser = $user->fresh(['profile', 'address', 'shops', 'managed_shop']);
            assert($freshUser instanceof User);

            return $freshUser;
        });
    }
}
