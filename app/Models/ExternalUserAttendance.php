<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalUserAttendance extends Model
{
    protected $fillable = [
        'external_user_id',
        'db_setlist_id',
        'attended_date',
        'venue',
    ];

    protected $casts = [
        'attended_date' => 'date',
    ];

    public function externalUser()
    {
        return $this->belongsTo(ExternalUser::class);
    }

    public function dbSetlist()
    {
        return $this->belongsTo(DbSetlist::class, 'db_setlist_id');
    }
}
