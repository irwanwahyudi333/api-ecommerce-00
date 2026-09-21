<?php

declare(strict_types=1);

namespace App\Modules\Withdraw\Actions;

use App\Models\User;
use App\Models\Withdraw;

final class ApproveWithdrawAction
{
    public function execute(Withdraw $withdraw, string $status, User $user): ?Withdraw
    {
        // Policy handles authorization for super admin
        $withdraw->status = $status;
        $withdraw->save();

        return $withdraw->fresh();
    }
}
