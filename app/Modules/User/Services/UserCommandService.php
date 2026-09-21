<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Jobs\SendForgetPasswordEmailJob;
use App\Models\User;
use App\Modules\User\Actions\AdminUpdateUserAction;
use App\Modules\User\Actions\CreateUserAction;
use App\Modules\User\Actions\DeleteUserAvatarAction; // tambahkan
use App\Modules\User\Actions\UpdateUserAction;
use App\Modules\User\Actions\UpdateUserAvatarAction; // tambahkan
use App\Modules\User\DTO\UpdateUserData;

/**
 * Berisi operasi tulis (write) seputar User. Logika tulis sesungguhnya
 * didelegasikan ke kelas Action masing-masing (Single Responsibility),
 * Service ini hanya bertindak sebagai orchestrator tipis.
 */
final class UserCommandService
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
        private readonly UpdateUserAction $updateUserAction,
        private readonly AdminUpdateUserAction $adminUpdateUserAction,
        private readonly UpdateUserAvatarAction $updateUserAvatarAction,
        private readonly DeleteUserAvatarAction $deleteUserAvatarAction,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function createByAdmin(array $validated): User
    {
        /** @var array{name: string, email: string, password: string, shop_id?: int|null, profile?: array<string, mixed>|null, address?: array<string, mixed>|null} $validated */
        return $this->createUserAction->execute($validated);
    }

    public function updateSelf(User $user, UpdateUserData $data): User
    {
        return $this->updateUserAction->execute($user, $data);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function updateByAdmin(User $target, array $validated): User
    {
        /** @var array{name?: string|null, email?: string|null, shop_id?: int|null} $validated */
        return $this->adminUpdateUserAction->execute($target, $validated);
    }

    public function updateEmail(User $user, string $newEmail): void
    {
        $user->forceFill([
            'email' => $newEmail,
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }

    /**
     * Email dikirim lewat Queue -> tidak memblokir response HTTP (proses berat).
     */
    public function sendResetPasswordEmail(string $email, string $token): void
    {
        SendForgetPasswordEmailJob::dispatch($email, $token);
    }

    public function updateAvatar(User $user, string $avatarUrl): User
    {
        return $this->updateUserAvatarAction->execute($user, $avatarUrl);
    }

    public function deleteAvatar(User $user): User
    {
        return $this->deleteUserAvatarAction->execute($user);
    }
}
