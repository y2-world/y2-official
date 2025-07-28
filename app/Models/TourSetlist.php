<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourSetlist extends Model
{
    protected $casts = [
        'setlist' => 'json',
        'encore' => 'json',
        'date1' => 'date',
        'date2' => 'date',
    ];

    protected $fillable = [
        'tour_id',
        'order_no',
        'date1',
        'date2',
        'subtitle',
        'setlist',
        'encore',
    ];

    public function setSetlistAttribute($value)
    {
        $this->attributes['setlist'] = json_encode(array_values($value));
    }

    public function setEncoreAttribute($value)
    {
        $this->attributes['encore'] = json_encode(array_values($value));
    }
}
