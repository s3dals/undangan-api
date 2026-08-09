<?php

namespace App\Models;

use Core\Model\Model;

final class Content extends Model
{
    protected $table = 'contents';

    protected $fillable = [
        'user_id',
        'content_key',
        'content_value',
    ];
}
