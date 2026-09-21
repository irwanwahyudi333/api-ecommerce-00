<?php

declare(strict_types=1);

namespace App\Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteUserAvatarAction
{
    public function execute(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $profile = $user->profile;
            if ($profile) {
                $profile->avatar = null;
                $profile->save();
            }

            $freshUser = $user->fresh('profile');
            assert($freshUser instanceof User);

            return $freshUser;
        });
    }
}
