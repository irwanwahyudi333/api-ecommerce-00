<?php

declare(strict_types=1);

namespace App\Modules\Refund\Http\Requests;

use App\Enums\RefundPolicyStatus;
use App\Enums\RefundPolicyTarget;
use App\Models\RefundPolicy;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundPolicyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user !== null && $user->can('create', RefundPolicy::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'target' => ['required', 'string', Rule::in(RefundPolicyTarget::getValues())],
            'status' => ['required', 'string', Rule::in(RefundPolicyStatus::getValues())],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'language' => ['nullable', 'string'],
        ];
    }
}
