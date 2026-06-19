<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbConcert extends Model
{
    protected $table = \'db_concerts\';

    protected $fillable = [
        'artist_id',
        'title',
        'type',
        'date1',
        'date2',
        'venue',
        'schedule',
        'text',
    ];

    protected $casts = [
        // setlist1~6 は削除済み。tour_setlistsテーブルで管理
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function songs()
    {
        return $this->hasMany('App\Models\DbSong');
    }

    public function getTourAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function tourSetlists()
    {
        return $this->hasMany(DbSetlist::class, 'tour_id', 'id');
    }
}
