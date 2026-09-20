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

class RefundPolicyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $policy = $this->route('refund_policy');
        if (! $policy instanceof RefundPolicy) {
            $id = is_numeric($policy) ? (int) $policy : 0;
            $policy = RefundPolicy::find($id);
        }

        return $policy instanceof RefundPolicy && $user->can('update', $policy);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'string', Rule::in(RefundPolicyTarget::getValues())],
            'status' => ['nullable', 'string', Rule::in(RefundPolicyStatus::getValues())],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'language' => ['nullable', 'string'],
        ];
    }
}
