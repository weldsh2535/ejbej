<?php

declare(strict_types=1);

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorize against an Event policy bulkDelete method (register EventPolicy accordingly)
        return $this->user()->can('bulkDelete', \App\Models\Event::class);
    }

    public function rules(): array
    {
        return [
            'ids'       => ['required', 'array', 'min:1'],
            'ids.*'     => ['integer', 'distinct', 'exists:events,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => __('Please select at least one event to delete.'),
            'ids.*.exists' => __('Selected event does not exist.'),
        ];
    }
}
