<?php

declare(strict_types=1);

namespace App\Modules\Terms\Actions;

use App\Models\Shop;
use App\Models\TermsAndConditions;
use App\Modules\Terms\DTO\TermsData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class CreateTermAction
{
    public function execute(TermsData $data): TermsAndConditions
    {
        $isApproved = ($data->shop_id === null || $data->shop_id === 0);
        $shop = $data->shop_id ? Shop::find($data->shop_id) : null;
        $issuedBy = $shop ? $shop->name : 'Super Admin';
        $type = $data->shop_id ? 'shop' : 'global';

        $term = TermsAndConditions::create([
            'title' => $data->title,
            'description' => $data->description,
            'language' => $data->language,
            'slug' => $data->slug ?? Str::slug((string) $data->title),
            'user_id' => $data->user_id,
            'shop_id' => $data->shop_id,
            'type' => $type,
            'issued_by' => $issuedBy,
            'is_approved' => $isApproved,
        ]);

        Cache::forget("terms_{$data->language}_*"); // Invalidate cache

        return $term;
    }
}
