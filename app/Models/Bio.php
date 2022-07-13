<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bio extends Model
{
    
    public function songs()
    {
        return $this->hasMany('App\Models\Song'); 
    }
}
