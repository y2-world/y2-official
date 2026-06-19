<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialNews extends Model
{
    protected $table = \'official_news\';

    protected $fillable = ['title', 'visible', 'hidden', 'text', 'date', 'image', 'published_at'];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
