<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fes extends Model
{
    protected $casts = ['setlist' => 'array',
        'date' => 'datetime:Y.m.d'
    ];

    protected $dates = ['date'];
}
