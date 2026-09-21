<?php

declare(strict_types=1);

namespace App\Modules\Terms\Actions;

use App\Models\TermsAndConditions;
use Illuminate\Support\Facades\Cache;

final class DisapproveTermAction
{
    public function execute(TermsAndConditions $term): TermsAndConditions
    {
        $term->is_approved = false;
        $term->save();

        Cache::forget("terms_{$term->language}_*"); // Invalidate cache

        return $term->refresh();
    }
}
