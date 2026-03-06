<?php
// app/Http/Resources/CategoryResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'icon' => $this->icon,
            'image' => $this->image,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'full_path' => $this->full_path,
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'active_products_count' => $this->when(isset($this->active_products_count), $this->active_products_count),
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
        ];
    }
}