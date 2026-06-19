<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{   
    public function setlists()
    {
        return $this->hasMany('App\SlSetlist'); 
        
    }

    protected $fillable = [
        'name', // ここに追加
        'visible',
    ];
}
