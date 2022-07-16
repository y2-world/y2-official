<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    public function songs()
    {
        return $this->hasMany('App\Models\Song'); 
    }

    public function getSetlistAttribute($setlist)
    {
        return explode(',', $setlist);
    }

    public function setSetlistAttribute($setlist) {
        $this->attributes['setlist'] = trim(implode($setlist, ','), ',');
    }

    public function getEncoreAttribute($encore)
    {
        return explode(',', $encore);
    }

    public function setEncoreAttribute($encore) {
        $this->attributes['encore'] = trim(implode($encore, ','), ',');
    }
}
