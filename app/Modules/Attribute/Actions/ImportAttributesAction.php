<?php

declare(strict_types=1);

namespace App\Modules\Attribute\Actions;

use App\Models\Attribute;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

final class ImportAttributesAction
{
    private const CACHE_KEY_PREFIX = 'attributes_';

    public function execute(UploadedFile $file, int $shopId, User $user): void
    {
        $path = $file->store('csv-files', 'public');
        $fullPath = Storage::path('public/'.$path);
        /** @var array<int, array<string, string>> $data */
        $data = $this->csvToArray($fullPath);

        if (empty($data)) {
            throw new \Exception('CSV file is empty or invalid');
        }

        foreach ($data as $attributeData) {
            if (! isset($attributeData['name'])) {
                throw new \Exception('WRONG_CSV');
            }
            unset($attributeData['id']); // Ensure ID is not set from CSV
            $attributeData['shop_id'] = $shopId;
            $attributeData['language'] = $attributeData['language'] ?? config('shop.default_language', 'id'); // Set default language if not present

            $values = [];
            if (isset($attributeData['values'])) {
                $valStr = is_scalar($attributeData['values']) ? (string) $attributeData['values'] : '';
                $values = explode(',', $valStr);
                unset($attributeData['values']);
            }

            // Assuming name and language are unique for an attribute within a shop
            $attribute = Attribute::firstOrCreate(
                ['name' => $attributeData['name'], 'language' => $attributeData['language'], 'shop_id' => $shopId],
                $attributeData
            );

            foreach ($values as $value) {
                if (! empty($value)) { // Only create if value is not empty
                    $attribute->values()->firstOrCreate(['value' => $value]);
                }
            }
        }

        $defaultLang = config('shop.default_language', 'id');
        $defaultLangStr = is_scalar($defaultLang) ? (string) $defaultLang : 'id';
        Cache::forget(self::CACHE_KEY_PREFIX.$defaultLangStr.'_*'); // Invalidate general cache
    }

    /**
     * Helper to convert CSV to array
     * @return array<int, array<string, string>>
     */
    private function csvToArray(string $filename, string $delimiter = ','): array
    {
        if (! file_exists($filename) || ! is_readable($filename)) {
            return [];
        }

        /** @var array<int, string>|null $header */
        $header = null;
        $data = [];
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (! $header) {
                    /** @var array<int, string> $row */
                    $header = $row;
                } else {
                    /** @var array<int, string> $row */
                    if (count($header) === count($row)) {
                        $data[] = array_combine($header, $row);
                    }
                }
            }
            fclose($handle);
        }

        return $data;
    }
}
