<?php

declare(strict_types=1);

namespace App\Http\Requests\Post;

use App\Enums\Hooks\PostFilterHook;
use App\Enums\PostPillar;
use App\Enums\PostStatus;
use App\Http\Requests\FormRequest;
use App\Support\Facades\Hook;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the controller using policies.
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize meta keys by slugifying them
        if ($this->has('meta_keys')) {
            $metaKeys = $this->input('meta_keys', []);
            // Ensure $metaKeys is always an array
            $metaKeys = is_array($metaKeys) ? $metaKeys : [];
            $sanitizedKeys = array_map(function ($key) {
                return ! empty($key) ? Str::slug($key, '_') : $key;
            }, $metaKeys);

            $this->merge([
                'meta_keys' => $sanitizedKeys,
            ]);
        }

        if ($this->has('pillars')) {
            $pillars = $this->input('pillars');

            if (is_null($pillars)) {
                $pillars = [];
            }

            if (! is_array($pillars)) {
                $pillars = [$pillars];
            }

            $this->merge([
                'pillars' => array_values(array_filter($pillars, static fn ($value) => ! is_null($value))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $postId = $this->post;
        $postStatuses = implode(',', array_map(fn ($status) => $status->value, PostStatus::cases()));

        return Hook::applyFilters(PostFilterHook::POST_UPDATE_VALIDATION_RULES, [
            /** @example "Updated: Laravel Development Best Practices" */
            'title' => 'required|string|max:255',

            /** @example "laravel-development-best-practices" */
            'slug' => 'nullable|string|max:255|unique:posts,slug,' . $postId,

            /** @example "<p>In this updated guide, we explore the best practices for Laravel development...</p>" */
            'content' => 'nullable|string',

            /** @example "Discover the latest best practices for Laravel application development." */
            'excerpt' => 'nullable|string',

            /** @example "published" */
            'status' => 'string',

            /** @example null */
            'parent_id' => 'nullable|exists:posts,id',

            /** @example null */
            'published_at' => 'nullable|date',

            /** Featured image - accepts both file uploads and media library IDs */
            'featured_image' => 'nullable',
            'featured_image.*' => 'nullable|numeric|exists:media,id',

            /** @example null */
            'remove_featured_image' => 'nullable',

            /** @example "author_info" */
            'meta_keys.*' => 'nullable|string|max:255|regex:/^[a-z0-9_]+$/',

            /** @example "John Doe, Senior Developer" */
            'meta_values.*' => 'nullable|string',

            /** @example "input" */
            'meta_types.*' => 'nullable|string|in:input,textarea,number,email,url,text,date,checkbox,select',

            /** @example "Author Name" */
            'meta_default_values.*' => 'nullable|string',

            /** Pillar selections */
            'pillars' => ['nullable', 'array'],
            'pillars.*' => ['string', Rule::in(PostPillar::values())],
        ], $postId);
    }
}
