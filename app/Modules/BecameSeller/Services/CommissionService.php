<?php

namespace App\Modules\BecameSeller\Services;

use App\Models\Commission;
use App\Modules\BecameSeller\DTO\CommissionData;
use Illuminate\Database\Eloquent\Collection;

class CommissionService
{
    /**
     * @return Collection<int, Commission>
     */
    public function getAll(): Collection
    {
        return Commission::get();
    }

    /**
     * @param  array<int, array<string, mixed>>  $commissions
     */
    public function storeCommissions(array $commissions, string $language): void
    {
        // Hapus semua komisi untuk language ini, lalu insert ulang (atau sync)
        Commission::where('language', $language)->delete();
        foreach ($commissions as $comm) {
            $data = CommissionData::fromArray($comm, $language);
            Commission::create([
                'min_balance' => $data->min_balance,
                'max_balance' => $data->max_balance,
                'commission' => $data->commission,
                'level' => $data->level,
                'sub_level' => $data->sub_level,
                'language' => $data->language,
            ]);
        }
    }
}
