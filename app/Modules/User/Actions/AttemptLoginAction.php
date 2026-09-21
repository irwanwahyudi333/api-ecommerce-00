<?php

declare(strict_types=1);

namespace App\Modules\User\Actions;

use App\Models\User;
use App\Modules\User\Services\UserSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Dipisah dari AuthService (Fat Service) agar logika lockout/brute-force
 * punya satu tanggung jawab tunggal dan mudah diuji terpisah.
 */
final class AttemptLoginAction
{
    public function __construct(
        private readonly UserSecurityService $userSecurityService
    ) {}

    /**
     * @return array{status: string, user?: User, locked_until?: Carbon}
     */
    public function execute(string $email, string $password, Request $request): array
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Timing-attack mitigation: tetap hash walau user tidak ditemukan.
            Hash::check('dummy_password', '$2y$10$'.str_repeat('0', 53));

            return ['status' => 'invalid'];
        }

        if ($user->locked_until?->isFuture()) {
            return ['status' => 'locked', 'locked_until' => $user->locked_until];
        }

        if (! $user->is_active || ! Hash::check($password, $user->password)) {
            $this->userSecurityService->handleFailedLoginAttempt($user, $request);

            return ['status' => 'invalid'];
        }

        if (! $user->hasVerifiedEmail()) {
            return ['status' => 'unverified', 'user' => $user];
        }

        $this->registerSuccessfulLogin($user, $request);

        $freshUser = $user->fresh();
        assert($freshUser instanceof User);

        return ['status' => 'success', 'user' => $freshUser];
    }

    private function registerSuccessfulLogin(User $user, Request $request): void
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_user_agent' => substr($request->userAgent() ?? 'Unknown', 0, 1000),
        ]);
    }
}
