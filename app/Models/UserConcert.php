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
        'schedule',
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

    // 日程表（schedule）から参加日・会場の候補を作る。読み方は公式のツアー（DbConcert::parseScheduleEntries）と同じ。
    // 日程表が無ければ候補は空で、参加日入力フォームは手入力になる
    public function parseScheduleEntries(): array
    {
        if (!filled($this->schedule)) {
            return [];
        }

        return (new DbConcert())->forceFill([
            'schedule' => $this->schedule,
            'date1' => $this->date1,
            'date2' => $this->date2,
        ])->parseScheduleEntries();
    }
}
