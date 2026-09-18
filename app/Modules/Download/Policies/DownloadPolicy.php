<?php

declare(strict_types=1);

namespace App\Modules\Download\Policies;

use App\Models\DigitalFile;
use App\Models\OrderedFile;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class DownloadPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, OrderedFile $orderedFile): bool
    {
        return $user->id === $orderedFile->customer_id;
    }

    public function generateToken(User $user, OrderedFile $orderedFile): bool
    {
        return $user->id === $orderedFile->customer_id;
    }

    public function download(User $user, DigitalFile $digitalFile): bool
    {
        /** @var OrderedFile|null $orderedFile */
        $orderedFile = $digitalFile->orderedFile;

        return $orderedFile && $orderedFile->customer_id === $user->id;
    }
}
