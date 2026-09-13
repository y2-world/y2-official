<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserConcert extends Model
{
    protected $fillable = [
        'external_user_id',
        'user_artist_id',
        'title',
        'type',
        'date1',
        'date2',
    ];

    public function externalUser()
    {
        return $this->belongsTo(ExternalUser::class);
    }

    public function artist()
    {
        return $this->belongsTo(UserArtist::class, 'user_artist_id');
    }

    public function setlists()
    {
        return $this->hasMany(UserSetlist::class);
    }

    // db_concerts.parseScheduleEntries()相当。ユーザー登録ツアーはschedule/venue列を持たないため、
    // 候補は作らず常に空を返し、参加日入力フォームでは常に手入力にフォールバックさせる。
    public function parseScheduleEntries(): array
    {
        return [];
    }
}
