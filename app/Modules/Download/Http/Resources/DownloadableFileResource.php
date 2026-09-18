<?php

declare(strict_types=1);

namespace App\Modules\Download\Http\Resources;

use App\Models\DigitalFile;
use App\Models\Order;
use App\Models\OrderedFile;
use App\Models\Product;
use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderedFile
 */
class DownloadableFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OrderedFile $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->id,
            'purchase_key' => $resource->purchase_key,
            'digital_file_id' => $resource->digital_file_id,
            'customer_id' => $resource->customer_id,
            'tracking_number' => $resource->tracking_number,
            'created_at' => $resource->created_at?->toISOString(),
            'updated_at' => $resource->updated_at?->toISOString(),
            'file' => $this->whenLoaded('file', function () use ($resource) {
                /** @var DigitalFile|null $file */
                $file = $resource->file;

                return [
                    'id' => $file?->id,
                    'attachment_id' => $file?->attachment_id,
                ];
            }),
            'order' => $this->whenLoaded('order', function () use ($resource) {
                /** @var Order|null $order */
                $order = $resource->order;

                return [
                    'tracking_number' => $order?->tracking_number,
                    'order_status' => $order?->order_status,
                ];
            }),
            'product' => $this->when($resource->file instanceof DigitalFile && $resource->file->fileable_type === 'App\\Models\\Product', function () use ($resource) {
                /** @var DigitalFile $file */
                $file = $resource->file;
                /** @var Product|null $product */
                $product = $file->fileable;

                return [
                    'id' => $file->fileable_id,
                    'shop' => $product && $product->shop ? clone $product->shop : null,
                ];
            }),
            'variation' => $this->when($resource->file instanceof DigitalFile && $resource->file->fileable_type === 'App\\Models\\Variation', function () use ($resource) {
                /** @var DigitalFile $file */
                $file = $resource->file;
                /** @var Variation|null $variation */
                $variation = $file->fileable;

                return [
                    'id' => $file->fileable_id,
                    'product' => $variation && $variation->product ? clone $variation->product : null,
                ];
            }),
        ];
    }
}
