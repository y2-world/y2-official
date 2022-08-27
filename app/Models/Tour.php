<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{   
    protected $casts = [
        // 'year' =>'json',
        'setlist1' =>'json',
        'setlist2' =>'json',
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
        $this->attributes['setlist1'] = json_encode(array_values($value));
    }

    public function setEncoreAttribute($value)
    {
        $this->attributes['setlist2'] = json_encode(array_values($value));
    }

    // public function setYearAttribute($value)
    // {
    //     $this->attributes['year'] = json_encode(array_values($value));
    // }
}
