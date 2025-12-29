<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = ['title', 'visible', 'hidden', 'text', 'date', 'image', 'published_at'];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
