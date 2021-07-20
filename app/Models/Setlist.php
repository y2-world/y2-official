<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setlist extends Model
{
    protected $casts = ['setlist' => 'array',
        'date' => 'datetime:Y.m.d'
    ];
}
