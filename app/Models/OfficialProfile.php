<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialProfile extends Model
{
    protected $table = \'official_profiles\';

    protected $fillable = [
        'name',
        'info',
        'text',
        'image',
    ];
}
