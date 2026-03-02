<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\PostPillar;
use Illuminate\Support\Str;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Determine featured image
        $featuredFromMedia = method_exists($this, 'getFeaturedImageUrl') ? $this->getFeaturedImageUrl() : null;
        if (! $featuredFromMedia) {
            try {
                $m = \Spatie\MediaLibrary\MediaCollections\Models\Media::query()
                    ->where('model_type', \App\Models\Post::class)
                    ->where('model_id', $this->id)
                    ->where('collection_name', 'featured')
                    ->first();
                if ($m) {
                    $featuredFromMedia = $m->getUrl();
                }
            } catch (\Throwable $t) {
                // ignore and continue to other fallbacks
            }
        }
        if (! $featuredFromMedia && method_exists($this, 'getMediaUrl')) {
            // Fallback to any image from default collection
            $featuredFromMedia = $this->getMediaUrl('default') ?? null;
        }
        $featuredFromContent = null;
        if (! $featuredFromMedia && ! empty($this->content)) {
            $html = (string) $this->content;
            if (preg_match('/<img[^>]+src=[\"\']([^\"\']+)[\"\']/i', $html, $m)) {
                $featuredFromContent = $m[1] ?? null;
            } elseif (preg_match('/<img[^>]+data-src=[\"\']([^\"\']+)[\"\']/i', $html, $m2)) {
                $featuredFromContent = $m2[1] ?? null;
            }
        }
        $featuredImage = $featuredFromMedia ?: $featuredFromContent;
        if (! $featuredImage && ! empty($this->featured_image)) {
            $featuredImage = str_starts_with($this->featured_image, 'http')
                ? $this->featured_image
                : asset('storage/' . ltrim((string)$this->featured_image, '/'));
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'featured_image' => $this->getFirstMediaUrl('featured'),
            'post_type' => $this->post_type,
            'status' => $this->status,
            'pillars' => $this->pillars,
            'pillar_labels' => collect($this->pillars)
                ->map(function ($value) {
                    $enum = PostPillar::tryFrom((string) $value);

                    return $enum?->label() ?? Str::of((string) $value)->replace('_', ' ')->title();
                })
                ->values()
                ->all(),
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'author' => new UserResource($this->whenLoaded('user')),
            'terms' => TermResource::collection($this->whenLoaded('terms')),
            'meta' => $this->whenLoaded('postMeta', function () {
                return $this->postMeta->pluck('meta_value', 'meta_key');
            }),
        ];
    }
}
