<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Modules\User\Http\Requests\ChangePasswordRequest;
use App\Modules\User\Services\UserSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserSecurityController extends BaseController
{
    public function __construct(
        private readonly UserSecurityService $securityService
    ) {}

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('changePassword', $user);

        /** @var string $oldPassword */
        $oldPassword = $request->old_password;
        /** @var string $newPassword */
        $newPassword = $request->new_password;

        if (! Hash::check($oldPassword, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Password lama tidak cocok.'],
            ]);
        }

        $passwordErrors = $this->securityService->validatePasswordStrength($newPassword);
        if (! empty($passwordErrors)) {
            throw ValidationException::withMessages([
                'new_password' => $passwordErrors,
            ]);
        }

        if ($this->securityService->isPasswordInHistory($user, $newPassword)) {
            throw ValidationException::withMessages([
                'new_password' => ['Password baru tidak boleh sama dengan password sebelumnya.'],
            ]);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        $this->securityService->recordPasswordChange($user, $newPassword);

        return $this->sendSuccess(null, 'Password berhasil diubah.');
    }

    public function logoutFromAllDevices(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('revokeSessions', $user);

        $this->securityService->logoutFromAllDevices($user);

        return $this->sendSuccess(null, 'Berhasil logout dari semua perangkat.');
    }

    public function viewActiveSessions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewSessions', $user);

        // This would query 'user_sessions' table or sanctum tokens with additional data
        $sessions = $user->tokens()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                // Add more session info if tracked in user_sessions table
            ];
        });

        return $this->sendSuccess($sessions, 'Sesi aktif berhasil diambil.');
    }

    public function revokeSession(Request $request, int $sessionId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('revokeSessions', $user);

        $token = $user->tokens()->where('id', $sessionId)->firstOrFail();

        if ($token->id === $user->currentAccessToken()->id) {
            throw ValidationException::withMessages([
                'session_id' => ['Tidak bisa mencabut sesi saat ini.'],
            ]);
        }

        $token->delete();

        return $this->sendSuccess(null, 'Sesi berhasil dicabut.');
    }
}
