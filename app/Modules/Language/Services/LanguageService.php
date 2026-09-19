<?php

declare(strict_types=1);

namespace App\Modules\Language\Services;

use App\Models\Language;
use App\Modules\Language\DTO\LanguageData;
use Illuminate\Database\Eloquent\Collection;

class LanguageService
{
    /**
     * @return Collection<int, Language>
     */
    public function getAll(): Collection
    {
        return Language::all();
    }

    public function find(int $id): Language
    {
        return Language::findOrFail($id);
    }

    public function create(LanguageData $data): Language
    {
        return Language::create([
            'language_name' => $data->language_name,
            'language_code' => $data->language_code,
            'flag' => $data->flag,
        ]);
    }

    public function update(Language $language, LanguageData $data): Language
    {
        $language->update([
            'language_name' => $data->language_name,
            'language_code' => $data->language_code,
            'flag' => $data->flag,
        ]);

        $language->refresh();

        return $language;
    }

    public function delete(Language $language): void
    {
        $language->delete();
    }
}
