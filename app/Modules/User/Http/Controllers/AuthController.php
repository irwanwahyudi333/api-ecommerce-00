<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Enums\Permission;
use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Modules\User\DTO\RegisterUserData;
use App\Modules\User\Http\Requests\ForgotPasswordRequest;
use App\Modules\User\Http\Requests\LoginRequest;
use App\Modules\User\Http\Requests\RegisterRequest;
use App\Modules\User\Http\Requests\ResetPasswordRequest;
use App\Modules\User\Http\Requests\SocialLoginRequest;
use App\Modules\User\Services\AuthService;
use App\Modules\User\Services\UserSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Controller hanya mengarahkan lalu lintas data (thin controller):
 * validasi -> DTO -> Service/Action -> Resource/Response.
 * Tidak ada query Eloquent atau business logic langsung di sini.
 */
final class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly UserSecurityService $securityService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var string|null $ipRaw */
        $ipRaw = $request->ip();
        $ip = $ipRaw ?? '';

        /** @var array{email: string, password: string} $validated */
        $validated = $request->validated();
        $email = $validated['email'];
        $password = $validated['password'];

        if (! $this->securityService->enforceRateLimit($ip, $email)) {
            return $this->sendError('Terlalu banyak percobaan login. Silakan coba lagi nanti.', 429);
        }

        $result = $this->authService->attemptLogin(
            $email,
            $password,
            $request
        );

        return match ($result['status']) {
            'invalid' => $this->sendError('Email atau password tidak valid.', 401),
            'locked' => $this->sendError('Akun dikunci sementara.', 423, [
                'locked_until' => $result['locked_until'] ?? null,
            ]),
            'unverified' => $this->sendError('Silakan verifikasi email Anda terlebih dahulu.', 403),
            'success' => $this->respondWithToken($result['user'] ?? null, $request), // Will handle null inside or assert
            default => $this->sendError('Unknown error', 500),
        };
    }

    private function respondWithToken(?User $user, Request $request): JsonResponse
    {
        if (! $user) {
            return $this->sendError('User not found', 404);
        }

        $token = $this->authService->issueToken($user, $request->userAgent());

        // Track session activity
        $this->securityService->trackSessionActivity($user, $request);

        return $this->sendSuccess([
            'token' => $token,
            'permissions' => $user->getPermissionNames(),
            'email_verified' => true,
            'role' => $user->getRoleNames()->first(),
            'session_id' => $user->currentAccessToken()?->id,
        ], 'Login successful');
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $requestedPermissionRaw = $request->validated('permission');
        $requestedPermission = is_string($requestedPermissionRaw) ? $requestedPermissionRaw : null;
        if ($requestedPermission === Permission::SUPER_ADMIN->value) {
            $requestedPermission = null;
        }

        /** @var array{name: string, email: string, password: string, profile?: array<string, mixed>|null, address?: array<string, mixed>|null} $validated */
        $validated = $request->validated();
        $data = RegisterUserData::fromValidated($validated, $requestedPermission);
        $user = $this->authService->register($data);

        $token = $this->authService->issueToken($user, 'register');

        return $this->sendSuccess([
            'token' => $token,
            'permissions' => $user->getPermissionNames(),
            'role' => $user->getRoleNames()->first(),
        ], 'Registration successful', 201);
    }

    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        /** @var array{provider: string, access_token: string} $validated */
        $validated = $request->validated();

        $user = $this->authService->socialLogin(
            $validated['provider'],
            $validated['access_token']
        );

        $token = $this->authService->issueToken($user, $validated['provider'].'-login');

        return $this->sendSuccess([
            'token' => $token,
            'permissions' => $user->getPermissionNames(),
            'role' => $user->getRoleNames()->first(),
        ], 'Social login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $this->authService->logout($user);
        }

        return $this->sendSuccess(true, 'Logged out');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->sendPasswordResetLink($request->validated());

            return $this->sendSuccess(true, 'If your email is in our system, you will receive a password reset link.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->resetPassword($request->validated());

            return $this->sendSuccess(true, 'Your password has been reset successfully.');
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage());
        }
    }

    /**
     * @return RedirectResponse
     */
    public function emailVerify(string $id, string $hash, Request $request)
    {
        if (! URL::hasValidSignature($request)) {
            abort(403, 'Invalid signature.');
        }

        $user = User::findOrFail($id);

        if (! hash_equals(
            sha1($user->getEmailForVerification()),
            $hash
        )) {
            abort(403, 'Invalid hash.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        /** @var string $frontendUrl */
        $frontendUrl = config('app.frontend_url');

        return redirect(
            $frontendUrl.'/email-verified?success=1'
        );
    }

    public function emailVerifyNotification(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent.',
        ]);
    }

    public function getEmailVerified(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'verified' => $user->hasVerifiedEmail(),
        ]);
    }
}
