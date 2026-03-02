<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

use App\Models\MediaFolder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $folder = $this->route('folder');
        $folderId = $folder instanceof \App\Models\MediaFolder ? $folder->getKey() : $folder;

        $parentRules = ['nullable', 'integer', 'exists:media_folders,id'];

        if ($folderId) {
            $parentRules[] = Rule::notIn([$folderId]);
        }

        return [
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'parent_id' => $parentRules,
            'slug' => ['nullable', 'string', 'max:191', Rule::unique('media_folders', 'slug')->ignore($folderId)],
            'order_column' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Please provide a folder name.'),
            'parent_id.not_in' => __('A folder cannot be its own parent.'),
            'parent_id.exists' => __('The selected parent folder does not exist.'),
            'slug.unique' => __('Another folder already uses this slug.'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $parentId = $this->input('parent_id');

        if ($parentId === '' || $parentId === null) {
            $this->merge(['parent_id' => null]);

            return;
        }

        $parentId = (int) $parentId;

        if (! MediaFolder::whereKey($parentId)->exists()) {
            $this->merge(['parent_id' => null]);

            return;
        }

        $this->merge(['parent_id' => $parentId]);
    }
}
