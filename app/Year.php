<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Year extends Model
{
    public function setlists()
    {
        return $this->hasMany('App\Setlist'); 
    }
}
