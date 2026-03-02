<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ProgramStatus;
use App\Enums\ProgramCategory;
use Illuminate\Validation\Rule;

class ProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'country' => 'nullable|string|max:255',
            'description' => 'required|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_bio' => 'nullable|string',
            'contact_details' => 'nullable|string',
            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.bio' => ['nullable', 'string'],
            'contacts.*.contact' => ['nullable', 'string'],
            'partners_involved' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', Rule::in(array_map(fn ($case) => $case->value, ProgramCategory::cases()))],
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['state'] = ['sometimes', Rule::in(ProgramStatus::values())];
        } else {
            $rules['state'] = ['required', Rule::in(ProgramStatus::values())];
        }

        return $rules;
    }
}
