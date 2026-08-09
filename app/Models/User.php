<?php

namespace App\Models;

use Core\Model\Model;
use Core\Valid\Hash;

final class User extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $typeKey = 'int';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'access_key',
        'is_filter',
        'can_edit',
        'can_delete',
        'can_reply',
        'show_home',
        'show_bride',
        'show_wedding_date',
        'show_gallery',
        'show_comment',
        'is_active',
        'tenor_key',
        'is_confetti_animation',
        'tz',
        'theme_primary_color',
        'theme_secondary_color',
        'theme_background_color',
        'theme_text_color',
        'theme_font',
        'is_custom_theme',
        'photo_home_url',
        'photo_bride_url',
        'photo_groom_url',
    ];

    protected $casts = [
        'is_filter' => 'bool',
        'can_edit' => 'bool',
        'can_delete' => 'bool',
        'can_reply' => 'bool',
        'show_home' => 'bool',
        'show_bride' => 'bool',
        'show_wedding_date' => 'bool',
        'show_gallery' => 'bool',
        'show_comment' => 'bool',
        'is_active' => 'bool',
        'is_confetti_animation' => 'bool',
        'is_custom_theme' => 'bool',
    ];

    public function __construct(array $data = [])
    {
        $this->fill($data);
    }

    protected function fakes(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->email(),
            'password' => Hash::make(fake()->text(8)),
        ];
    }

    public function setAsAdmin(): void
    {
        $this->attributes['is_admin'] = true;
    }

    public function setAsNonAdmin(): void
    {
        $this->attributes['is_admin'] = false;
    }

    public function isAdmin(): bool
    {
        return boolval($this->attributes['is_admin']);
    }

    public function isActive(): bool
    {
        return boolval($this->attributes['is_active']);
    }

    public function isFilter(): bool
    {
        return boolval($this->attributes['is_filter']);
    }

    public function canReply(): bool
    {
        return boolval($this->attributes['can_reply']);
    }

    public function canEdit(): bool
    {
        return boolval($this->attributes['can_edit']);
    }

    public function canDelete(): bool
    {
        return boolval($this->attributes['can_delete']);
    }

    public function getTimezone(): string|null
    {
        return $this->attributes['tz'];
    }
}
