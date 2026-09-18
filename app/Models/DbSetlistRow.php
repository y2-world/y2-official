<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbSetlistRow extends Model
{
    protected $table = 'db_setlist_rows';

    protected $fillable = [
        'tour_id',
        'row',
        'order_no',
        'title',
    ];

    public function tour()
    {
        return $this->belongsTo(DbConcert::class, 'tour_id', 'id');
    }
}
