<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserConcert extends Model
{

    // SCHEDULE は「10月8日 (水) 会場」の形にそろえて保存する（管理画面・マイページ・取り込みのどこから保存しても同じ形になるように）
    protected static function booted(): void
    {
        static::saving(function (self $concert) {
            if (filled($concert->schedule) && ($concert->isDirty('schedule') || $concert->isDirty('date1') || $concert->isDirty('date2'))) {
                $concert->schedule = \App\Support\ScheduleFormatter::format($concert->schedule, $concert->date1, $concert->date2)['text'];
            }
        });
    }
    protected $fillable = [
        'external_user_id',
        'user_artist_id',
        // 管理画面（公式の DbConcert と同じ画面）で使う別名。中身は user_artist_id
        'artist_id',
        'title',
        'type',
        'date1',
        'date2',
        'schedule',
        // 公式のテーブルと同じ項目（管理画面で同じ画面を使うため）
        'venue',
        'text',
    ];

    public function externalUser()
    {
        return $this->belongsTo(ExternalUser::class);
    }

    // マイページの画面から送られてきたツアーの種類（0 ツアー / 1 単発ライブ / 2 イベント。公式と同じ）。
    // 前の画面の「フェス・複数アーティスト出演イベント」のチェック（is_fes）だけが来たときは 2、どちらも無ければツアー
    public static function typeFromRequest(\Illuminate\Http\Request $request): int
    {
        $type = $request->input('tour_type');
        if (in_array((string) $type, ['0', '1', '2'], true)) {
            return (int) $type;
        }

        return $request->boolean('is_fes') ? 2 : 0;
    }

    public function artist()
    {
        return $this->belongsTo(UserArtist::class, 'user_artist_id');
    }

    // 管理画面で公式の DbConcert と同じ名前で扱えるようにする別名（artist_id = user_artist_id）
    // 管理画面の編集画面は、モデルの中身（toArray）をフォームに入れるので、別名も入るようにする
    protected $appends = ['artist_id'];

    public const ADMIN_COLUMNS = ['artist_id' => 'user_artist_id'];

    public function getArtistIdAttribute()
    {
        return $this->attributes['user_artist_id'] ?? null;
    }

    public function setArtistIdAttribute($value): void
    {
        $this->attributes['user_artist_id'] = $value;
    }

    public function scopeOfArtist($query, $artistId)
    {
        return $query->where('user_artist_id', $artistId);
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
