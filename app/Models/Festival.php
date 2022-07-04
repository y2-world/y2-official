<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Festival extends Model
{
    protected $table = 'festivals';

    protected $casts = [
        'setlist' =>'json',
        'encore' =>'json'
    ];

    public function getSetlistAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function setSetlistAttribute($value)
    {
        $this->attributes['setlist'] = json_encode(array_values($value));
    }

    public function setEncoreAttribute($value)
    {
        $this->attributes['encore'] = json_encode(array_values($value));
    }

}
