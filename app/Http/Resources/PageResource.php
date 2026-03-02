<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'hero_description' => $this->hero_description,
            'hero_image' => $this->hero_image ? asset('storage/' . $this->hero_image) : null,
            'section' => $this->section,
            'is_custom_section' => $this->is_custom_section,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'show_in_nav' => $this->show_in_nav,
            'show_in_footer' => $this->show_in_footer,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'url' => $this->url,
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                ];
            }),
            'updated_by' => $this->whenLoaded('updatedBy', function () {
                return [
                    'id' => $this->updatedBy->id,
                    'name' => $this->updatedBy->name,
                ];
            }),
        ];
    }
}