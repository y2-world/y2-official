<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    protected $fillable = [
        'title',
        'artist_id',
        'album_id',
        'single_id',
        'year',
        'text',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($song) {
            // Convert empty strings to null for foreign keys
            if ($song->album_id === '') {
                $song->album_id = null;
            }
            if ($song->single_id === '') {
                $song->single_id = null;
            }
        });
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id');
    }

    public function single()
    {
        return $this->belongsTo(Single::class, 'single_id');
    }

    public function bios()
    {
        return $this->belongsTo(Bio::class, 'year');
    }

    public function getSingle()
    {   
        return $this->belongsTo(Single::class);
    }
}

