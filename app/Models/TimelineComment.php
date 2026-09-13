<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimelineComment extends Model
{
    protected $fillable = [
        'external_user_attendance_id',
        'external_user_id',
        'body',
    ];

    public function attendance()
    {
        return $this->belongsTo(ExternalUserAttendance::class, 'external_user_attendance_id');
    }

    public function externalUser()
    {
        return $this->belongsTo(ExternalUser::class);
    }
}
