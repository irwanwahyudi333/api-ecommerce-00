<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Modules\Attachment\Http\Requests\AttachmentRequest;
use App\Modules\Attachment\Services\AttachmentWriteService;
use App\Modules\User\Actions\ChangePasswordAction;
use App\Modules\User\DTO\UpdateUserData;
use App\Modules\User\Http\Requests\ChangePasswordRequest;
use App\Modules\User\Http\Requests\UserUpdateRequest;
use App\Modules\User\Http\Resources\UserResource;
use App\Modules\User\Services\UserCommandService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ProfileController extends BaseController
{
    public function __construct(
        private readonly UserCommandService $userCommandService,
        private readonly AttachmentWriteService $attachmentWriteService
    ) {}

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user) {
            /** @var string $message */
            $message = config('notice.NOT_AUTHORIZED');
            throw new AuthorizationException($message);
        }

        // Eager loading eksplisit -> mencegah N+1 saat relasi diakses di Resource/response.
        $user->load(['profile', 'wallet', 'address', 'shops.balance', 'managed_shop.balance'])
            ->loadLastOrder();

        return $this->sendSuccess(new UserResource($user), 'Profile data');
    }

    public function update(UserUpdateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{name?: string|null, email?: string|null, profile?: array<string, mixed>|null, address?: array<string, mixed>|null} $validated */
        $validated = $request->validated();
        $data = UpdateUserData::fromValidated($validated);
        $updated = $this->userCommandService->updateSelf($user, $data);

        return $this->sendSuccess(new UserResource($updated), 'User updated');
    }

    public function getUser(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->sendSuccess($user, 'User data retrieved successfully');
    }

    public function updateUser(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var array<string, mixed> $data */
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
        ]);

        $user->fill($data);
        $user->save();

        return $this->sendSuccess($user, 'User data updated successfully');
    }

    public function deleteUser(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->delete();

        return $this->sendSuccess(null, 'User deleted successfully');
    }

    public function changePassword(ChangePasswordRequest $request, ChangePasswordAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array{old_password: string, new_password: string} $validated */
        $validated = $request->validated();
        $success = $action->execute($user, $validated['old_password'], $validated['new_password']);

        if (! $success) {
            return $this->sendError('Password lama tidak sesuai.', 400);
        }

        return $this->sendSuccess(null, 'Password changed');
    }

    public function updateEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc,dns', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation failed', 422, $validator->errors());
        }

        /** @var User $user */
        $user = $request->user();
        /** @var array{email: string} $validated */
        $validated = $validator->validated();
        $this->userCommandService->updateEmail($user, $validated['email']);

        return $this->sendSuccess(null, 'Email updated, please verify your new email');
    }

    public function updateAvatar(AttachmentRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('updateAvatar', $user);

        $uploaded = $this->attachmentWriteService->upload($request->getAttachmentData());
        if (empty($uploaded)) {
            throw new HttpException(400, 'Failed to upload avatar.');
        }

        /** @var array{url: string} $avatarData */
        $avatarData = $uploaded[0]; // Ambil avatar pertama dari hasil upload
        $updatedUser = $this->userCommandService->updateAvatar($user, $avatarData['url']);

        return $this->sendSuccess(new UserResource($updatedUser), 'Avatar updated successfully.');
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('deleteAvatar', $user);

        // Pastikan ada avatar sebelum dihapus
        if (! $user->profile || ! $user->profile->avatar) {
            throw new HttpException(404, 'No avatar to delete.');
        }

        // Asumsi avatar disimpan sebagai array JSON di user_profiles.avatar, dan mengandung 'path'
        /** @var string|null $avatarPath */
        $avatarPath = $user->profile->avatar['path'] ?? null;
        if ($avatarPath) {
            $this->attachmentWriteService->deleteByPath($avatarPath); // Asumsi ada method deleteByPath di AttachmentWriteService
        }
        $updatedUser = $this->userCommandService->deleteAvatar($user);

        return $this->sendSuccess(new UserResource($updatedUser), 'Avatar deleted successfully.');
    }
}
