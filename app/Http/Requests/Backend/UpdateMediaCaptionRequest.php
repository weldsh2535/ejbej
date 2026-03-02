<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaCaptionRequest extends FormRequest
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
        return [
            'caption' => ['nullable', 'string', 'max:500'],
        ];
    }
}
