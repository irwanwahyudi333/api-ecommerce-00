<?php

declare(strict_types=1);

namespace App\Modules\User\Actions;

use App\Enums\Permission;
use App\Enums\Role;
use App\Jobs\GiveSignupPointsJob;
use App\Models\Settings;
use App\Models\User;
use App\Modules\User\DTO\RegisterUserData;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class RegisterUserAction
{
    public function execute(RegisterUserData $data): User
    {
        // Whitelist eksplisit -> mencegah Mass Assignment (shop_id, is_active, dll
        // tidak pernah ikut walau ada di payload, karena DTO tidak membawanya sama sekali).
        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            if ($data->profile) {
                /** @var array<string, mixed> $profileData */
                $profileData = Arr::only(
                    $data->profile,
                    ['avatar', 'bio', 'socials']
                );
                $user->profile()->create($profileData);
            }

            if ($data->address) {
                /** @var array<string, mixed> $addressData */
                $addressData = Arr::only(
                    $data->address,
                    ['street_address', 'city', 'state', 'zip', 'country']
                );
                $user->address()->create($addressData);
            }

            // Validasi nilai final di sini (bukan hanya di FormRequest) -> defense in depth.
            // Tidak pernah mengizinkan super_admin lewat jalur registrasi publik.
            $grantedPermission = $data->requestedPermission === Permission::STORE_OWNER->value
                ? Permission::STORE_OWNER
                : Permission::CUSTOMER;

            $user->givePermissionTo($grantedPermission->value);
            $user->assignRole($grantedPermission === Permission::STORE_OWNER
                ? Role::STORE_OWNER->value
                : Role::CUSTOMER->value);

            return $user;
        });

        $settings = Settings::getData();
        if (data_get($settings, 'options.useMustVerifyEmail', true)) {
            event(new Registered($user));
        }

        // Proses non-kritis (poin signup) didelegasikan ke Queue, tidak memblokir response.
        $signupPointsRaw = data_get($settings, 'options.signupPoints', 0);
        $signupPoints = is_numeric($signupPointsRaw) ? (int) $signupPointsRaw : 0;
        if ($signupPoints > 0) {
            GiveSignupPointsJob::dispatch($user->id, $signupPoints);
        }

        return $user;
    }
}
