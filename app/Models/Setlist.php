<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setlist extends Model
{
    protected $casts = ['setlist' => 'array',
        'date' => 'datetime:Y.m.d'
    ];

    protected $dates = ['date'];

     // 追加: 'name' を fillable プロパティに設定
     protected $fillable = [
        'artist_id', // ここに追加
        'name',
        'title',
        'date',
        'venue',
        'setlist',
        'encore',
        // 他のフィールドも必要に応じて追加
    ];
    
}
