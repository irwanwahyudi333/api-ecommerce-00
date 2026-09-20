<?php

declare(strict_types=1);

namespace App\Modules\Review\Http\Requests;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ReviewUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();
        /** @var Review $review */
        $review = $this->route('review');

        return $user && $user->can('update', $review);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'photos' => ['nullable', 'array'],
        ];
    }
}
