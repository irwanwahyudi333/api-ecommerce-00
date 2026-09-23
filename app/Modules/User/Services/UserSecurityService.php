<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserSecurityService
{
    private const MAX_FAILED_ATTEMPTS = 5;

    private const LOCKOUT_DURATION = 15; // minutes

    private const SESSION_MAX_AGE = 30; // days

    private const PASSWORD_HISTORY_LIMIT = 5;

    public function enforceRateLimit(string $ip, string $identifier): bool
    {
        $cacheKey = "rate_limit:auth:{$ip}:{$identifier}";
        /** @var int $attempts */
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= 10) {
            return false;
        }

        Cache::put($cacheKey, $attempts + 1, 60); // 1 minute

        return true;
    }

    /**
     * @return array<int, string>
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 12) {
            $errors[] = 'Password minimal 12 karakter';
        }

        if (! preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password harus mengandung huruf kapital';
        }

        if (! preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password harus mengandung huruf kecil';
        }

        if (! preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password harus mengandung angka';
        }

        if (! preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password harus mengandung karakter spesial';
        }

        return $errors;
    }

    public function isPasswordInHistory(User $user, string $newPassword): bool
    {
        $passwordHistory = Cache::remember(
            "password_history:{$user->id}",
            86400,
            function () use ($user) {
                return DB::table('password_history')
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(self::PASSWORD_HISTORY_LIMIT)
                    ->pluck('password_hash')
                    ->toArray();
            }
        );

        /** @var string $oldHash */
        foreach ($passwordHistory as $oldHash) {
            if (password_verify($newPassword, $oldHash)) {
                return true;
            }
        }

        return false;
    }

    public function recordPasswordChange(User $user, string $newPassword): void
    {
        DB::table('password_history')->insert([
            'user_id' => $user->id,
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Trim history
        $idsToKeep = DB::table('password_history')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(self::PASSWORD_HISTORY_LIMIT)
            ->pluck('id');

        DB::table('password_history')
            ->where('user_id', $user->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();

        Log::info('Password changed', [
            'user_id' => $user->id,
            'action' => 'password_change',
        ]);
    }

    public function handleFailedLoginAttempt(User $user, Request $request): void
    {
        DB::transaction(function () use ($user, $request) {
            $user->increment('failed_login_attempts');

            if ($user->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {
                $user->update(['locked_until' => now()->addMinutes(self::LOCKOUT_DURATION)]);

                Log::warning('Account locked due to failed login attempts', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'attempts' => $user->failed_login_attempts,
                ]);
            }

            Log::info('Failed login attempt', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? 'Unknown', 0, 500),
            ]);
        });
    }

    public function rotateTokens(User $user): void
    {
        // Delete old tokens
        $user->tokens()
            ->where('created_at', '<', now()->subDays(self::SESSION_MAX_AGE))
            ->delete();

        Log::info('Token rotation performed', [
            'user_id' => $user->id,
            'action' => 'token_rotation',
        ]);
    }

    public function logoutFromAllDevices(User $user): void
    {
        $user->tokens()->delete();

        Log::info('Logged out from all devices', [
            'user_id' => $user->id,
            'action' => 'logout_all_devices',
        ]);
    }

    public function trackSessionActivity(User $user, Request $request): void
    {
        $sessionData = [
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? 'Unknown', 0, 1000),
            'last_activity_at' => now(),
        ];

        DB::table('user_sessions')->updateOrInsert(
            ['user_id' => $user->id, 'token_id' => $user->currentAccessToken()?->id],
            $sessionData
        );
    }
}
