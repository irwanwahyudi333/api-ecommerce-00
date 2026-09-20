<?php

declare(strict_types=1);

namespace App\Modules\Refund\Services;

use App\Enums\Permission;
use App\Models\RefundPolicy;
use App\Models\Shop;
use App\Models\User;
use App\Modules\Refund\DTO\RefundPolicyData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RefundPolicyService
{
    /**
     * @return Builder<RefundPolicy>
     */
    public function getPoliciesQuery(Request $request, ?User $user = null): Builder
    {
        $defaultLang = config('shop.default_language', 'id');
        $language = is_string($request->get('language')) ? $request->get('language') : (is_string($defaultLang) ? $defaultLang : 'id');
        $query = RefundPolicy::where('language', $language);

        // Filter by shop_id if not super_admin
        if ($request->has('shop_id')) {
            if (! $user || ! $user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
                $query->where('shop_id', $request->get('shop_id'));
            }
        }

        return $query;
    }

    public function findPolicy(string $value, string $language): RefundPolicy
    {
        if (is_numeric($value)) {
            return RefundPolicy::where('id', $value)->where('language', $language)->firstOrFail();
        }

        return RefundPolicy::where('slug', $value)->where('language', $language)->firstOrFail();
    }

    public function createPolicy(RefundPolicyData $data, User $user): RefundPolicy
    {
        $policyData = $data->toArray();
        if (! $user->hasPermissionTo(Permission::SUPER_ADMIN->value) && $user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            $shop = $user->shops()->first();
            if ($shop instanceof Shop) {
                $policyData['shop_id'] = $shop->id;
            }
        }

        /** @var RefundPolicy $policy */
        $policy = RefundPolicy::create($policyData);

        return $policy;
    }

    public function updatePolicy(RefundPolicy $policy, RefundPolicyData $data, User $user): RefundPolicy
    {
        $policyData = $data->toArray();
        if (! $user->hasPermissionTo(Permission::SUPER_ADMIN->value) && $user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            $shop = $user->shops()->first();
            if ($shop instanceof Shop) {
                $policyData['shop_id'] = $shop->id;
            }
        }

        $policy->update($policyData);

        $fresh = $policy->fresh();

        return $fresh instanceof RefundPolicy ? $fresh : $policy;
    }

    public function deletePolicy(RefundPolicy $policy, User $user): void
    {
        $policy->delete();
    }
}
