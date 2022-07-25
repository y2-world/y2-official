<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apbank extends Model
{
    protected $casts = [
        'year' =>'json',
        'setlist' =>'json',
    ];

    public function songs()
    {
        return $this->hasMany('App\Models\Song'); 
    }

    public function getTourAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function setSetlistAttribute($value)
    {
        $this->attributes['setlist'] = json_encode(array_values($value));
    }

    public function setYearAttribute($value)
    {
        $this->attributes['year'] = json_encode(array_values($value));
    }
}
