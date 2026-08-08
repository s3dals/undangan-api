<?php

namespace App\Request;

use Core\Valid\Form;

class UpdateUserRequest extends Form
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'str', 'trim', 'min:1', 'max:40'],
            'old_password' => ['nullable', 'str', 'trim', 'min:8', 'max:20'],
            'new_password' => ['nullable', 'str', 'trim', 'min:8', 'max:20'],
            'tenor_key' => ['nullable', 'str', 'min:1', 'max:100'],
            'tz' => ['nullable', 'str', 'trim', 'min:1', 'max:70'],
            'theme_primary_color' => ['nullable', 'str', 'trim', 'min:7', 'max:7'],
            'theme_secondary_color' => ['nullable', 'str', 'trim', 'min:7', 'max:7'],
            'theme_background_color' => ['nullable', 'str', 'trim', 'min:7', 'max:7'],
            'theme_font' => ['nullable', 'str', 'trim', 'min:1', 'max:30'],
        ];
    }
}
