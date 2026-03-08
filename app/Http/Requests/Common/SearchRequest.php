<?php
// app/Http/Requests/SearchRequest.php

namespace App\Http\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a product name to search',
            'name.min' => 'Search term must be at least 1 character',
            'name.max' => 'Search term cannot exceed 255 characters',
        ];
    }
}