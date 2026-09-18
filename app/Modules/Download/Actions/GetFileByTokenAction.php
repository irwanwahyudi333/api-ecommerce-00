<?php

declare(strict_types=1);

namespace App\Modules\Download\Actions;

use App\Models\DigitalFile;
use App\Models\DownloadToken;

class GetFileByTokenAction
{
    public function execute(string $token): ?DigitalFile
    {
        $downloadToken = DownloadToken::with('file')->where('token', $token)->first();
        if (! $downloadToken) {
            return null;
        }
        $digitalFile = $downloadToken->file;
        $downloadToken->delete();

        return $digitalFile instanceof DigitalFile ? $digitalFile : null;
    }
}
