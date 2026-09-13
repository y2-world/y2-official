<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalUserAttendance extends Model
{
    protected $fillable = [
        'external_user_id',
        'db_setlist_id',
        'user_setlist_id',
        'attended_date',
        'venue',
        'rating',
    ];

    protected $casts = [
        'attended_date' => 'date',
    ];

    public function externalUser()
    {
        return $this->belongsTo(ExternalUser::class);
    }

    public function comments()
    {
        return $this->hasMany(TimelineComment::class)->with('externalUser')->orderBy('created_at');
    }

    public function dbSetlist()
    {
        return $this->belongsTo(DbSetlist::class, 'db_setlist_id');
    }

    public function userSetlist()
    {
        return $this->belongsTo(UserSetlist::class, 'user_setlist_id');
    }

    // db_setlist_id / user_setlist_id のどちらか一方だけが埋まっているため、
    // 呼び出し側が公式/ユーザー登録の別を意識せずに済むよう、実際に紐づく方を返す統一アクセサ。
    // 必ず ->load(['dbSetlist.tour.artist', 'userSetlist.concert.artist']) 済みの状態で呼ぶこと。
    public function getAttendedSetlistAttribute()
    {
        return $this->db_setlist_id ? $this->dbSetlist : $this->userSetlist;
    }

    // DbConcert（tour()経由）とUserConcert（concert()経由）はリレーション名が違うため、
    // どちらの場合でも同じ書き方（$attendance->attendedTour）でツアー・ライブを取得できるようにする。
    public function getAttendedTourAttribute()
    {
        $setlist = $this->attendedSetlist;
        if (!$setlist) {
            return null;
        }
        return $this->db_setlist_id ? $setlist->tour : $setlist->concert;
    }
}
