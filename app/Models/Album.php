<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    public function songs()
    {
        return $this->hasMany('App\Models\Song'); 
    }
}
