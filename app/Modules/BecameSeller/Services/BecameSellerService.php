<?php

namespace App\Modules\BecameSeller\Services;

use App\Models\BecameSeller;
use App\Modules\BecameSeller\DTO\BecameSellerData;

class BecameSellerService
{
    /**
     * @return array<string, mixed>
     */
    public function getData(string $language): array
    {
        $seller = BecameSeller::getData($language);
        if ($seller instanceof BecameSeller) {
            /** @var array<string, mixed> $pageOptions */
            $pageOptions = $seller->page_options;

            return $pageOptions;
        }

        return [];
    }

    public function storeOrUpdate(BecameSellerData $data): BecameSeller
    {
        $existing = BecameSeller::where('language', $data->language)->first();
        if ($existing instanceof BecameSeller) {
            $existing->update(['page_options' => $data->page_options]);

            /** @var BecameSeller $fresh */
            $fresh = $existing->fresh();

            return $fresh;
        }

        /** @var BecameSeller $created */
        $created = BecameSeller::create([
            'page_options' => $data->page_options,
            'language' => $data->language,
        ]);

        return $created;
    }

    public function getFirst(): ?BecameSeller
    {
        return BecameSeller::first();
    }
}
