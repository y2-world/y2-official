<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'kana',
        'romaji',
    ];

    public function setlists()
    {
        return $this->hasMany(Setlist::class);
    }

    public function songs()
    {
        return $this->hasMany(Song::class);
    }
}
