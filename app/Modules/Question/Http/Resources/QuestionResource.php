<?php

declare(strict_types=1);

namespace App\Modules\Question\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Question
 */
class QuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'shop_id' => $this->shop_id,
            'user_id' => $this->user_id,
            'question' => $this->question,
            'answer' => $this->answer,
            'positive_feedbacks_count' => $this->positive_feedbacks_count,
            'negative_feedbacks_count' => $this->negative_feedbacks_count,
            'my_feedback' => $this->my_feedback,
            'abusive_reports_count' => $this->abusive_reports_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'product' => $this->whenLoaded('product'),
        ];
    }
}
