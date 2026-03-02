<?php

declare(strict_types=1);

namespace App\Http\Requests\Backend;

class UploadFolderMediaRequest extends MediaUploadRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
