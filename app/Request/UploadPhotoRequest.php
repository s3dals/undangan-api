<?php

namespace App\Request;

use Core\Valid\Form;

class UploadPhotoRequest extends Form
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Base64 of an image already resized by the browser. No max here:
            // the controller measures the decoded bytes, which is the size that
            // actually matters.
            'data' => ['required', 'str'],
            'type' => ['required', 'str', 'trim', 'min:9', 'max:30'],
        ];
    }
}
