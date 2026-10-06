<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalUserAttendance extends Model
{
    protected $fillable = [
        'external_user_id',
        'db_setlist_id',
        'user_setlist_id',
        'db_concert_id',
        'user_concert_id',
        'attended_date',
        'venue',
        'selected_daily_songs',
        'rating',
    ];

    protected $casts = [
        'attended_date' => 'date',
        'selected_daily_songs' => 'array',
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

    // 参加予定（パターン未選択）の記録が指すツアー・ライブ
    public function dbConcert()
    {
        return $this->belongsTo(DbConcert::class, 'db_concert_id');
    }

    public function userConcert()
    {
        return $this->belongsTo(UserConcert::class, 'user_concert_id');
    }

    // 参加予定：まだセットリストパターンを選んでいない記録（統計には数えない・投稿にセトリを載せない）
    public function getIsPlannedAttribute(): bool
    {
        return !$this->db_setlist_id && !$this->user_setlist_id;
    }

    // 参加予定の記録に、セットリストを追加できるか（公演日以降）
    public function getCanAddSetlistAttribute(): bool
    {
        return $this->is_planned && $this->attended_date && $this->attended_date->toDateString() <= now()->toDateString();
    }

    // 公式のツアー（db_concerts）の記録か。参加予定の記録はパターンが無いので、ツアーの列でも見る
    public function getIsOfficialAttribute(): bool
    {
        return (bool) ($this->db_setlist_id || $this->db_concert_id);
    }

    // 統計など「実際に参加したライブ」だけを数えるところで使う（参加予定を除く）
    public function scopeWithSetlist($query)
    {
        return $query->where(fn ($q) => $q->whereNotNull('db_setlist_id')->orWhereNotNull('user_setlist_id'));
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
            // 参加予定の記録は、ツアー・ライブを直接指している
            return $this->db_concert_id ? $this->dbConcert : ($this->user_concert_id ? $this->userConcert : null);
        }
        return $this->db_setlist_id ? $setlist->tour : $setlist->concert;
    }

    // 参加記録から、アーティストごとに初めてライブに行った日（"official-{id}" / "user-{id}" => 'Y-m-d'）。
    // スタンプ帳の並び（初めてライブに行った順）に使う。参加日が無い記録はライブの開始日で代える
    public static function firstDatesByArtistRef($attendances): array
    {
        $dates = [];
        foreach ($attendances as $attendance) {
            $tour = $attendance->attendedTour;
            $artistId = $attendance->is_official ? $tour?->artist_id : $tour?->user_artist_id;
            if (!$artistId) {
                continue;
            }
            $ref = ($attendance->is_official ? 'official-' : 'user-') . $artistId;
            $date = $attendance->attended_date?->format('Y-m-d') ?? substr((string) ($tour->date1 ?? $tour->date ?? ''), 0, 10);
            if ($date !== '' && (!isset($dates[$ref]) || $date < $dates[$ref])) {
                $dates[$ref] = $date;
            }
        }
        return $dates;
    }
}
