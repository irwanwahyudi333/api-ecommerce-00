<?php

namespace App\Modules\Settings\Services;

use App\Models\Settings;
use Illuminate\Support\Arr;

class NotificationRecipientService
{
    /**
     * @return array<string, bool>
     */
    public function getWhichUserWillGetEmail(string $eventType, string $language): array
    {
        // Logika menentukan siapa yang akan menerima email berdasarkan settings
        // Contoh sederhana: always notify vendor and admin
        $settings = Settings::getData($language);
        $options = (array) $settings->options;
        $vendorEnabled = (bool) Arr::get($options, 'notify_vendor', true);
        $adminEnabled = (bool) Arr::get($options, 'notify_admin', true);

        return [
            'vendor' => $vendorEnabled,
            'admin' => $adminEnabled,
        ];
    }
}
