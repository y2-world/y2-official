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
        'visible',
    ];

    public function setlists()
    {
        return $this->hasMany(Setlist::class);
    }

    public function songs()
    {
        return $this->hasMany(Song::class);
    }

    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    public function singles()
    {
        return $this->hasMany(Single::class);
    }

    public function bios()
    {
        return $this->hasMany(Bio::class)->orderBy('year', 'asc');
    }

    public function getYearsAttribute()
    {
        $singleYears = $this->singles()->whereNotNull('date')->get()->map(fn($s) => (int) date('Y', strtotime($s->date)));
        $albumYears = $this->albums()->whereNotNull('date')->get()->map(fn($a) => (int) date('Y', strtotime($a->date)));
        return $singleYears->merge($albumYears)->unique()->sort()->values()->map(fn($y) => (object)['year' => $y]);
    }

    public function tours()
    {
        return $this->hasMany(Tour::class);
    }
}
