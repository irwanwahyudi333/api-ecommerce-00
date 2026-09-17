<?php

declare(strict_types=1);

namespace App\Modules\Attachment\Http\Requests;

use App\Modules\Attachment\DTO\AttachmentData;
use Illuminate\Foundation\Http\FormRequest;

class AttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attachment' => ['required', 'array'],
            'attachment.*' => ['file', 'mimes:jpeg,png,jpg,gif,svg,webp,pdf,doc,docx,xls,xlsx,csv,txt,zip', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'attachment.required' => 'At least one file is required.',
            'attachment.*.file' => 'Each attachment must be a valid file.',
            'attachment.*.max' => 'Each attachment must not exceed 20MB.',
        ];
    }

    public function getAttachmentData(): AttachmentData
    {
        return AttachmentData::fromRequest($this->validated());
    }
}
