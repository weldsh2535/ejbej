<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OthersPublicResource extends JsonResource
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

        return [
            'id' => $this->id,
            'title' => $this->title,
            'creator' => $this->creator,
            'description' => $this->description,
            'resource_type' => $this->resource_type,
            'subject_area' => $this->subject_area,
            'tags' => $this->tags ?? [],
            'mime_type' => $this->mime_type,
            'file_url' => $fileUrl,
            'is_featured' => (bool) $this->is_featured,
            'published_at' => optional($this->published_at)->toISOString(),
            'download_count' => (int) ($this->download_count ?? 0),
            'view_count' => (int) ($this->view_count ?? 0),
        ];
    }
}
