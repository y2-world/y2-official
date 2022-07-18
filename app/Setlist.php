<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setlist extends Model
{
    protected $table = 'setlists';

    protected $casts = [
        'setlist' =>'json',
        'encore' =>'json',
        'fes_setlist' =>'json',
        'fes_encore' =>'json',
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


    public function setFesSetlistAttribute($value)
    {
        $this->attributes['fes_setlist'] = json_encode(array_values($value));
    }

    public function setFesEncoreAttribute($value)
    {
        $this->attributes['fes_encore'] = json_encode(array_values($value));
    }
    public function artist()
    {   
        return $this->belongsTo(Artist::class, 'artist_id');
    }
}
