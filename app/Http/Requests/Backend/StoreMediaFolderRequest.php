<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

use App\Models\MediaFolder;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('media.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:media_folders,id'],
            'slug' => ['nullable', 'string', 'max:191', 'unique:media_folders,slug'],
            'order_column' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Please provide a folder name.'),
            'name.max' => __('Folder names must be less than :max characters.', ['max' => 191]),
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
