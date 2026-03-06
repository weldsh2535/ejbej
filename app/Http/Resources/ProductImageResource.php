<?php
// app/Http/Resources/ProductImageResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'thumbnail_urls' => [
                'small' => $this->getThumbnailUrl('small'),
                'medium' => $this->getThumbnailUrl('medium'),
                'large' => $this->getThumbnailUrl('large'),
            ],
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'size' => $this->formatted_size,
            'sort_order' => $this->sort_order,
            'is_primary' => $this->is_primary,
            'alt_text' => $this->alt_text,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}