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
            'theme_text_color' => ['nullable', 'str', 'trim', 'min:7', 'max:7'],
            'theme_font' => ['nullable', 'str', 'trim', 'min:1', 'max:30'],
            'theme_font_arabic' => ['nullable', 'str', 'trim', 'min:1', 'max:30'],
            'theme_direction' => ['nullable', 'str', 'trim', 'min:3', 'max:4'],
            // No min: an empty value is how the divider goes back to the text colour.
            'theme_divider_color' => ['nullable', 'str', 'trim', 'max:7'],
            'is_custom_theme' => ['nullable', 'bool'],
            'rsvp_deadline' => ['nullable', 'str', 'trim', 'min:10', 'max:10'],
        ];
    }
}
