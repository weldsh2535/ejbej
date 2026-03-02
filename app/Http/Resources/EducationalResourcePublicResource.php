<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EducationalResourcePublicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fileUrl = null;
        if (!empty($this->file_path)) {
            try {
                $fileUrl = Storage::disk('public')->url($this->file_path);
            } catch (\Throwable $t) {
                $fileUrl = null;
            }
        }
        $thumbUrl = null;
        if (!empty($this->thumbnail_path)) {
            try {
                $thumbUrl = Storage::disk('public')->url($this->thumbnail_path);
            } catch (\Throwable $t) {
                $thumbUrl = null;
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'creator' => $this->creator,
            'resource_type' => $this->resource_type,
            'educational_level' => $this->educational_level,
            'subject_area' => $this->subject_area,
            'language' => $this->language,
            'duration_minutes' => $this->duration_minutes,
            'thumbnail' => $thumbUrl,
            'file_url' => $fileUrl,
            'embed_code' => $this->embed_code,
            'tags' => $this->tags ?? [],
            'is_featured' => (bool) $this->is_featured,
            'published_at' => optional($this->published_at)->toISOString(),
            'download_count' => (int) ($this->download_count ?? 0),
            'view_count' => (int) ($this->view_count ?? 0),
        ];
    }
}
