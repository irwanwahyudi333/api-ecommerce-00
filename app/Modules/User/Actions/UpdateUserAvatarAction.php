<?php

declare(strict_types=1);

namespace App\Modules\User\Actions;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateUserAvatarAction
{
    public function execute(User $user, string $avatarUrl): User
    {
        return DB::transaction(function () use ($user, $avatarUrl) {
            /** @var Profile $profile */
            $profile = $user->profile()->firstOrCreate([]);
            $profile->avatar = ['thumbnail' => $avatarUrl, 'original' => $avatarUrl]; // Simpan path avatar
            $profile->save();

            $freshUser = $user->fresh('profile');
            assert($freshUser instanceof User);

            return $freshUser;
        });
    }
}
