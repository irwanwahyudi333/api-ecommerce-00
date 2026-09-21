<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Models\User;
use App\Models\UserSession;
use App\Modules\User\Actions\AttemptLoginAction;
use App\Modules\User\Actions\RegisterUserAction;
use App\Modules\User\DTO\RegisterUserData;
use App\Modules\User\Exceptions\AuthException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * AuthService kini hanya orchestrator tipis (sebelumnya "Fat Service"
 * berisi logika lockout, social-login, dsb). Logika berat dipindah ke
 * Action terpisah agar masing-masing punya satu tanggung jawab & mudah diuji.
 */
final class AuthService
{
    public function __construct(
        private readonly AttemptLoginAction $attemptLoginAction,
        private readonly RegisterUserAction $registerUserAction,
        private readonly SocialLoginService $socialLoginService,
        private readonly UserSecurityService $userSecurityService
    ) {}

    /**
     * @return array{status:string, user?:User, locked_until?:Carbon}
     */
    public function attemptLogin(string $email, string $password, Request $request): array
    {
        return $this->attemptLoginAction->execute($email, $password, $request);
    }

    public function register(RegisterUserData $data): User
    {
        $user = $this->registerUserAction->execute($data);
        $this->userSecurityService->recordPasswordChange($user, $data->password); // Record initial password

        return $user;
    }

    public function socialLogin(string $provider, string $accessToken): User
    {
        return $this->socialLoginService->handle($provider, $accessToken);
    }

    public function logout(User $user): bool
    {
        $token = $user->currentAccessToken();

        if ($token) {
            $deleted = (bool) $token->delete();
            // Optional: Remove session tracking entry
            UserSession::where('user_id', $user->id)
                ->where('token_id', $token->id)
                ->delete();

            return $deleted;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthException
     */
    public function sendPasswordResetLink(array $data): string
    {
        $status = Password::sendResetLink($data);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new AuthException((string) $status);
        }

        return (string) $status;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthException
     */
    public function resetPassword(array $data): string
    {
        /** @var string $status */
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->setRememberToken(Str::random(60));

            $user->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw new AuthException($status);
        }

        return $status;
    }

    public function issueToken(User $user, ?string $deviceName = null): string
    {
        $name = substr($deviceName ?: 'auth_token', 0, 255);
        $token = $user->createToken($name);

        UserSession::create([
            'user_id' => $user->id,
            'token_id' => (string) $token->accessToken->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'last_activity_at' => now(),
        ]);

        return $token->plainTextToken;
    }
}
