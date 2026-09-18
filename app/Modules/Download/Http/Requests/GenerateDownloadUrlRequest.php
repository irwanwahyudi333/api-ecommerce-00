<?php

declare(strict_types=1);

namespace App\Modules\Download\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateDownloadUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'digital_file_id' => ['required', 'exists:digital_files,id'],
        ];
    }
}
