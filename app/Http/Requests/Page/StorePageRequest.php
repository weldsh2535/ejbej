<?php

declare(strict_types=1);

namespace App\Http\Requests\Page;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Page::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('pages', 'slug')
            ],
            'content' => 'required|string',
            'hero_description' => 'nullable|string|max:1000',
            'hero_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'section' => 'required|string|max:100',
            'is_custom_section' => 'nullable|boolean',
            'custom_section' => 'nullable|string|max:100|required_if:is_custom_section,true',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'show_in_nav' => 'nullable|boolean',
            'show_in_footer' => 'nullable|boolean',
            'status' => 'nullable|string|in:published,draft,archived',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens. It cannot start or end with a hyphen.',
            'slug.unique' => 'This slug is already in use. Please choose a different one.',
            'custom_section.required_if' => 'Custom section name is required when using custom section.',
            'content.required' => 'Page content is required.',
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'URL slug',
            'content' => 'page content',
            'section' => 'page section',
            'custom_section' => 'custom section name',
            'meta_title' => 'meta title',
            'meta_description' => 'meta description',
            'show_in_nav' => 'show in navigation',
            'show_in_footer' => 'show in footer',
        ];
    }

    public function prepareForValidation()
    {
        // Auto-generate slug from title if not provided and title exists
        if ($this->has('title') && (!$this->has('slug') || empty($this->slug))) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->title)
            ]);
        }

        // Handle custom section logic
        if ($this->has('is_custom_section') && $this->is_custom_section && $this->has('custom_section')) {
            $this->merge([
                'section' => $this->custom_section
            ]);
        }

        // Set default values
        $this->merge([
            // 'show_in_nav' => $this->show_in_nav ?? true,
            // 'show_in_footer' => $this->show_in_footer ?? false,
            'status' => $this->status ?? 'draft',
            // 'is_custom_section' => $this->is_custom_section ?? false,
        ]);
    }
}