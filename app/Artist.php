<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{

    public function setlists()
    {
        return $this->hasMany('App\Setlist'); 
    }
}
