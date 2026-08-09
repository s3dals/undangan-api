<?php

namespace App\Models;

use Core\Model\Model;

final class Gallery extends Model
{
    protected $table = 'galleries';

    protected $fillable = [
        'user_id',
        'url',
        'storage_path',
    ];
}
