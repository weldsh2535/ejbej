<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\FormRequest;

class StoreEmailSubscriptionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $preferences = [
            'wants_news',
            'wants_events',
            'wants_announcements',
            'wants_scholarships',
            'wants_newsletters',
        ];

        foreach ($preferences as $key) {
            if (! $this->has($key)) {
                $this->merge([$key => true]);
            }
        }
    }

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
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'wants_news' => ['sometimes', 'boolean'],
            'wants_events' => ['sometimes', 'boolean'],
            'wants_announcements' => ['sometimes', 'boolean'],
            'wants_scholarships' => ['sometimes', 'boolean'],
            'wants_newsletters' => ['sometimes', 'boolean'],
        ];
    }
}
