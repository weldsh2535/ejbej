<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add your authorization logic
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'brand' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            
            // Image validation
            'images' => ['sometimes', 'array', 'min:1', 'max:10'],
            'images.*' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=4096,max_height=4096'
            ],
            'primary_image_index' => [
                'sometimes',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->has('images') && $value >= count($this->file('images'))) {
                        $fail('Primary image index must be within the number of uploaded images.');
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'images.max' => 'You can upload a maximum of 10 images.',
            'images.min' => 'Please upload at least 1 image.',
            'images.*.max' => 'Each image must not exceed 2MB.',
            'images.*.dimensions' => 'Image dimensions must be between 100px and 4096px.',
            'images.*.mimes' => 'Images must be of type: jpeg, png, jpg, gif, webp.'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true)
        ]);
    }
}