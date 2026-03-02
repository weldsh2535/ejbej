<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

use App\Support\Helper\MediaHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->can('media.create');
    }

    public function rules(): array
    {
        $limits = MediaHelper::getUploadLimits();

        $rules = [
            'files' => 'required|array|max:' . $limits['max_file_uploads'],
            'files.*' => [
                'required',
                'file',
            ],
            'captions' => ['nullable', 'array'],
            'captions.*' => ['nullable', 'string', 'max:500'],
        ];

        // Add MIME type restrictions for demo mode
        if (config('app.demo_mode', false)) {
            $allowedMimeTypes = implode(',', MediaHelper::getAllowedMimeTypesForDemo());
            $rules['files.*'][] = 'mimetypes:' . $allowedMimeTypes;
        }

        return $rules;
    }

    public function messages(): array
    {
        $limits = MediaHelper::getUploadLimits();

        $messages = [
            'files.required' => __('Please select at least one file to upload.'),
            'files.max' => __('You can upload a maximum of :max files at once.', ['max' => $limits['max_file_uploads']]),
            'files.*.required' => __('Each file is required.'),
            'files.*.file' => __('Each upload must be a valid file.'),
            'captions.array' => __('Captions must be submitted as an array.'),
            'captions.*.string' => __('Captions must be text values.'),
            'captions.*.max' => __('Captions may not be greater than :max characters.', ['max' => 500]),
        ];

        // Add demo mode specific message
        if (config('app.demo_mode', false)) {
            $messages['files.*.mimetypes'] = __('In demo mode, only images, videos, PDFs, and documents (Word, Excel, PowerPoint, text files) are allowed.');
        }

        return $messages;
    }

    protected function prepareForValidation(): void
    {
        // Check for PHP upload errors before Laravel validation
        $phpError = MediaHelper::checkPhpUploadError();
        if ($phpError) {
            // Add the error to the validator
            $this->getValidatorInstance()->after(function ($validator) use ($phpError) {
                $validator->errors()->add('php_upload_limit', $phpError['message']);
            });
        }
    }
}
