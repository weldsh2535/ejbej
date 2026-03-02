<?php

declare(strict_types=1);

namespace App\Http\Requests\Page;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeletePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorize against an Event policy bulkDelete method (register EventPolicy accordingly)
        return $this->user()->can('bulkDelete', \App\Models\Page::class);
    }

    public function rules(): array
    {
        return [
            'ids'       => ['required', 'array', 'min:1'],
            'ids.*'     => ['integer', 'distinct', 'exists:pages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => __('Please select at least one page to delete.'),
            'ids.*.exists' => __('Selected page does not exist.'),
        ];
    }
}
