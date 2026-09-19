<?php

namespace App\Modules\Product\Http\Requests;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric'],
            'sale_price' => ['nullable', 'lte:price'],
            'type_id' => ['required', 'exists:types,id'],
            'shop_id' => ['required', 'exists:shops,id'],
            'manufacturer_id' => ['nullable', 'exists:manufacturers,id'],
            'author_id' => ['nullable', 'exists:authors,id'],
            'product_type' => ['required', 'in:'.implode(',', array_column(ProductType::cases(), 'value'))],
            'categories' => ['array'],
            'tags' => ['array'],
            'language' => ['nullable', 'string'],
            'dropoff_locations' => ['array'],
            'pickup_locations' => ['array'],
            'digital_file' => ['array'],
            'variations' => ['array'],
            'variation_options' => ['array'],
            'quantity' => ['nullable', 'integer'],
            'unit' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:10000'],
            'sku' => ['string', 'unique:variation_options,sku'],
            'image' => ['array'],
            'gallery' => ['array'],
            'video' => ['array'],
            'metas' => ['array'],
            'metas.*.key' => ['required', 'string', 'in:meta_title,meta_description,meta_keywords,custom_label,custom_value'],
            'metas.*.value' => ['nullable', 'string'],
            'status' => ['string', 'in:'.implode(',', array_column(ProductStatus::cases(), 'value'))],
            'height' => ['nullable', 'string'],
            'length' => ['nullable', 'string'],
            'width' => ['nullable', 'string'],
            'external_product_url' => ['nullable', 'string'],
            'external_product_button_text' => ['nullable', 'string'],
            'in_stock' => ['boolean'],
            'is_taxable' => ['boolean'],
            'is_digital' => ['boolean'],
            'is_external' => ['boolean'],
            'is_rental' => ['boolean'],
            'variation_options.upsert.*.sku' => ['string', 'unique:variation_options,sku'],
        ];
    }
}
