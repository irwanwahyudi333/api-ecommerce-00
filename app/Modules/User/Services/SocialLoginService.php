<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Enums\Permission;
use App\Enums\Role;
use App\Jobs\GiveSignupPointsJob;
use App\Models\Settings;
use App\Models\User;
use App\Modules\User\Exceptions\SocialLoginException;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

/**
 * Dipisah dari AuthService karena alur social-login punya kompleksitas
 * & exception handling sendiri (Single Responsibility).
 */
final class SocialLoginService
{
    private const ALLOWED_PROVIDERS = ['facebook', 'google'];

    public function handle(string $provider, string $accessToken): User
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            /** @var string $message */
            $message = config('notice.PLEASE_LOGIN_USING_FACEBOOK_OR_GOOGLE');
            throw new SocialLoginException($message);
        }

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);
        /** @var \Laravel\Socialite\Two\User $socialUser */
        $socialUser = $driver->stateless()->userFromToken($accessToken);
        $email = $socialUser->getEmail();

        if (! $email) {
            throw new SocialLoginException('Email not provided by social provider');
        }

        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $alreadyLinked = $existingUser->providers()->where('provider', $provider)->exists();
            if (! $alreadyLinked) {
                throw new SocialLoginException(
                    "Email already registered. Please link your {$provider} account from your profile settings."
                );
            }
        }

        return DB::transaction(function () use ($email, $provider, $socialUser, $existingUser): User {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['email_verified_at' => now(), 'name' => $socialUser->getName() ?? 'User']
            );

            $user->providers()->updateOrCreate(
                ['provider' => $provider, 'provider_user_id' => $socialUser->getId()],
                []
            );

            if ($socialUser->getAvatar()) {
                $user->profile()->updateOrCreate(
                    ['customer_id' => $user->id],
                    ['avatar' => ['thumbnail' => $socialUser->getAvatar(), 'original' => $socialUser->getAvatar()]]
                );
            }

            $isStaffOrAbove = $user->hasAnyRole([
                Role::SUPER_ADMIN->value, Role::STORE_OWNER->value, Role::STAFF->value,
            ]);

            if (! $isStaffOrAbove) {
                if (! $user->hasPermissionTo(Permission::CUSTOMER->value)) {
                    $user->givePermissionTo(Permission::CUSTOMER->value);
                }
                if (! $user->hasRole(Role::CUSTOMER->value)) {
                    $user->assignRole(Role::CUSTOMER->value);
                }
            }

            if (! $existingUser) {
                $settings = Settings::getData();
                $pointsRaw = data_get($settings, 'options.signupPoints', 0);
                $points = is_numeric($pointsRaw) ? (int) $pointsRaw : 0;
                if ($points > 0) {
                    GiveSignupPointsJob::dispatch($user->id, $points);
                }
            }

            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
                'last_login_user_agent' => substr(request()->userAgent() ?? 'Unknown', 0, 1000),
            ]);

            return $user;
        });
    }
}
