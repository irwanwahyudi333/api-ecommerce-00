<?php

namespace App\Modules\Terms\Http\Resources;

use App\Models\TermsAndConditions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TermsAndConditions
 */
class TermsConditionResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'issued_by' => $this->issued_by,
            'is_approved' => $this->is_approved,
            'language' => $this->language,
            'translated_languages' => $this->translated_languages,
        ];
    }
}
