<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    protected $fillable = [
        'title',
    ];

    protected $casts = [
        // setlist1~6 は削除済み。tour_setlistsテーブルで管理
    ];

    public function songs()
    {
        return $this->hasMany('App\Models\Song');
    }

    public function getTourAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function tourSetlists()
    {
        return $this->hasMany(TourSetlist::class, 'tour_id', 'id');
    }
}
