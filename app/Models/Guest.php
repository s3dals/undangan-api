<?php

namespace App\Models;

use Core\Model\Model;

final class Guest extends Model
{
    protected $table = 'guests';

    protected $fillable = [
        'user_id',
        'name',
        'greeting',
        'token',
        'max_guests',
        'status',
        'guest_count',
        'responded_at',
    ];

    protected $casts = [
        'max_guests' => 'int',
        'guest_count' => 'int',
    ];
}
