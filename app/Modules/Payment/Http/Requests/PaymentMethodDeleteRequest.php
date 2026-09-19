<?php

declare(strict_types=1);

namespace App\Modules\Payment\Http\Requests;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $method = $this->findPaymentMethod();

        if (! $method) {
            return false;
        }

        /** @var User $user */
        $user = $this->user();

        return $user->can('delete', $method);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    private function findPaymentMethod(): ?PaymentMethod
    {
        $id = $this->route('id');

        if (! is_numeric($id)) {
            return null;
        }

        return PaymentMethod::query()
            ->where('id', (int) $id)
            ->whereHas('paymentGateway', function ($query) {
                /** @var User $user */
                $user = $this->user();
                $query->where('user_id', $user->id);
            })
            ->first();
    }

    public function getPaymentMethod(): PaymentMethod
    {
        $method = $this->findPaymentMethod();

        if (! $method) {
            abort(404, 'Payment method not found or you do not have permission to access it.');
        }

        return $method;
    }
}
