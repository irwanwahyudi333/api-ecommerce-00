<?php

declare(strict_types=1);

namespace App\Modules\Terms\Services;

use App\Enums\Permission;
use App\Models\Shop;
use App\Models\TermsAndConditions;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class TermsQueryService
{
    /**
     * @return LengthAwarePaginator<int, TermsAndConditions>
     */
    public function getTermsQuery(Request $request, ?Authenticatable $user): LengthAwarePaginator
    {
        $defaultLang = config('shop.default_language', 'id');
        $defaultLang = is_scalar($defaultLang) ? (string) $defaultLang : 'id';
        $language = isset($request->language) && is_scalar($request->language) ? (string) $request->language : $defaultLang;
        $limit = is_scalar($request->limit) ? (int) $request->limit : 10;
        $shopId = is_scalar($request->shop_id) ? (int) $request->shop_id : null;

        $query = TermsAndConditions::with('shop')->where('language', $language);

        // This hasPermission logic should be handled by policy in controller.
        // For query purposes, we'll build the query based on user's role/permissions if available.
        if ($user) {
            if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
                return $query->paginate($limit);
            }

            if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
                if ($shopId && $this->userCanAccessShop($user, $shopId)) {
                    return $query->where('shop_id', $shopId)->paginate($limit);
                }

                /** @var User $user */
                return $query->whereIn('shop_id', $user->shops->pluck('id'))->paginate($limit);
            }

            if ($user->hasPermissionTo(Permission::STAFF->value)) {
                if ($shopId && $this->userCanAccessShop($user, $shopId)) {
                    return $query->where('shop_id', $shopId)->paginate($limit);
                }

                /** @var User $user */
                return $query->where('shop_id', $user->shop_id)->paginate($limit);
            }
        }

        // Guest or customer, or authenticated user without specific roles for terms management
        if ($shopId) {
            return $query->where('shop_id', $shopId)->where('is_approved', true)->paginate($limit);
        }

        return $query->where('is_approved', true)->paginate($limit);
    }

    public function find(string $slug, string $language): TermsAndConditions
    {
        return TermsAndConditions::where('slug', $slug)->where('language', $language)->firstOrFail();
    }

    public function findOrFail(int $id): TermsAndConditions
    {
        return TermsAndConditions::findOrFail($id);
    }

    private function userCanAccessShop(Authenticatable $user, int $shopId): bool
    {
        $shop = Shop::find($shopId);
        if (! $shop) {
            return false;
        }

        /** @var User $user */
        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            return $shop->owner_id === $user->id;
        }

        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $shop->staffs->contains($user->id);
        }

        return false;
    }
}
