<?php

namespace App\Modules\PaymentMethod\Http\Requests;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;

class SetDefaultPaymentMethodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
        ];
    }

    public function getPaymentMethod(): PaymentMethod
    {
        /** @var PaymentMethod $method */
        $method = PaymentMethod::findOrFail($this->input('payment_method_id'));

        return $method;
    }
}
