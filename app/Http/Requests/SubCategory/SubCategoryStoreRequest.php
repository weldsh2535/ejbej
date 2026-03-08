<?php

namespace App\Http\Requests\SubCategory;

use Illuminate\Foundation\Http\FormRequest;

class SubCategoryStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Set to false if you want authorization logic
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique',
            'slug' => 'required|string|max:255|unique',
            'description' => 'nullable|string',
            'category_id' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'is_active' => 'sometimes|boolean'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Subcategory name is required',
            'name.unique' => 'This subcategory name already exists',
            'slug.required' => 'Slug is required',
            'slug.unique' => 'This slug already exists',
            'category_id.required' => 'Please select a parent category',
            'category_id.exists' => 'Selected category does not exist',
            'image.image' => 'File must be an image',
            'image.max' => 'Image size should not exceed 2MB'
        ];
    }
}