<?php

namespace App\Modules\Terms\Services;

use App\Enums\Permission;
use App\Models\Shop;
use App\Models\TermsAndConditions;
use App\Models\User;
use App\Modules\Terms\DTO\TermsData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TermsService
{
    public function hasPermission(?Authenticatable $user, ?int $shopId): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }
        if (! $shopId) {
            return false;
        }

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

    /**
     * @return Builder<TermsAndConditions>
     */
    public function getTermsQuery(Request $request, ?Authenticatable $user)
    {
        $defaultLang = config('shop.default_language', 'id');
        $defaultLang = is_scalar($defaultLang) ? (string) $defaultLang : 'id';
        $language = isset($request->language) && is_scalar($request->language) ? (string) $request->language : $defaultLang;
        $query = TermsAndConditions::with('shop')->where('language', $language);

        if ($user && $user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return $query;
        }

        if ($user && $user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            $shopId = is_scalar($request->shop_id) ? (int) $request->shop_id : null;
            if ($shopId && $this->hasPermission($user, $shopId)) {
                return $query->where('shop_id', $shopId);
            }

            /** @var User $user */
            return $query->whereIn('shop_id', $user->shops->pluck('id'));
        }

        if ($user && $user->hasPermissionTo(Permission::STAFF->value)) {
            $shopId = is_scalar($request->shop_id) ? (int) $request->shop_id : null;
            if ($shopId && $this->hasPermission($user, $shopId)) {
                return $query->where('shop_id', $shopId);
            }

            /** @var User $user */
            return $query->where('shop_id', $user->shop_id);
        }

        // Guest or customer
        if ($request->shop_id) {
            return $query->where('shop_id', $request->shop_id)->where('is_approved', true);
        }

        return $query->where('is_approved', true);
    }

    public function store(TermsData $data): TermsAndConditions
    {
        $isApproved = ($data->shop_id === null || $data->shop_id === 0);
        $shop = $data->shop_id ? Shop::find($data->shop_id) : null;
        $issuedBy = $shop ? $shop->name : 'Super Admin';
        $type = $data->shop_id ? 'shop' : 'global';

        return TermsAndConditions::create([
            'title' => $data->title,
            'description' => $data->description,
            'language' => $data->language,
            'slug' => $data->slug,
            'user_id' => $data->user_id,
            'shop_id' => $data->shop_id,
            'type' => $type,
            'issued_by' => $issuedBy,
            'is_approved' => $isApproved,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TermsAndConditions $term, array $data): TermsAndConditions
    {
        $term->update($data);

        return $term->refresh();
    }

    public function approve(TermsAndConditions $term): void
    {
        $term->is_approved = true;
        $term->save();
    }

    public function disapprove(TermsAndConditions $term): void
    {
        $term->is_approved = false;
        $term->save();
    }

    public function delete(TermsAndConditions $term): void
    {
        $term->delete();
    }
}
