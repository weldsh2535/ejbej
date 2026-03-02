<?php

declare(strict_types=1);

namespace App\Http\Requests\Term;

use App\Enums\Hooks\TermFilterHook;
use App\Http\Requests\FormRequest;
use App\Support\Facades\Hook;
use App\Services\Content\ContentService;
use Illuminate\Validation\Rule;

class UpdateTermRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the controller using policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $termId = $this->term ?? $this->route('id');

        $rules = [
            /** @example "Web Development" */
            'name' => 'required|string|max:255|unique:terms,name,'.$termId,

            /** @example "web-development" */
            'slug' => 'nullable|string|max:255|unique:terms,slug,'.$termId,

            /** @example "Topics related to web development and programming." */
            'description' => 'nullable|string',

            /** @example null */
            'parent_id' => 'nullable|exists:terms,id',

            /** @example null */
            'context_post_type' => 'nullable|string',
            'remove_featured_image' => 'nullable',
            'post_types' => ['required', 'array', 'min:1'],
            'post_types.*' => ['string'],
        ];

        // Add featured image validation if taxonomy supports it.
        $taxonomyName = $this->route('taxonomy');
        $taxonomyModel = app(ContentService::class)->getTaxonomies()->where('name', $taxonomyName)->first();

        if ($taxonomyModel && $taxonomyModel->show_featured_image) {
            /** @example null */
            $rules['featured_image'] = [
                'nullable',
                function ($attribute, $value, $fail) {
                    // Allow either file upload or media ID.
                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        // Validate as image file.
                        if (! in_array($value->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                            $fail('The featured image must be a valid image file (JPEG, PNG, GIF, or WebP).');
                        }
                        if ($value->getSize() > 2048 * 1024) { // 2MB in bytes
                            $fail('The featured image must not be larger than 2MB.');
                        }
                    } elseif (is_string($value)) {
                        // Validate as media ID or URL.
                        if (! is_numeric($value)) {
                            // If it's not numeric, check if it's a valid URL.
                            if (! filter_var($value, FILTER_VALIDATE_URL)) {
                                $fail('The featured image must be a valid media ID or URL.');
                            }
                        } else {
                            // If it's numeric, verify the media exists.
                            $mediaExists = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($value);
                            if (! $mediaExists) {
                                $fail('The selected media does not exist.');
                            }
                        }
                    }
                },
            ];
        }

        $postTypeNames = app(ContentService::class)
            ->getPostTypes()
            ->keys()
            ->map(fn ($name) => strtolower((string) $name))
            ->values()
            ->all();

        $rules['post_types.*'][] = Rule::in($postTypeNames);

        return Hook::applyFilters(TermFilterHook::TERM_UPDATE_VALIDATION_RULES, $rules, $taxonomyName);
    }
}
