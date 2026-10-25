<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// マイページで作ったアーティストのアルバム（ミニアルバム・ベストを含む）。公式の DbAlbum と同じ持ち方
class UserAlbum extends Model
{
    protected $fillable = [
        'user_artist_id',
        // 管理画面（公式と同じ画面）で使う別名。中身は user_artist_id
        'artist_id',
        'external_user_id',
        'title',
        'date',
        'mini', 'best', 'special',
        'tracklist',
        // 公式のテーブルと同じ項目（管理画面で同じ画面を使うため）
        'album_id',
        'text',
    ];

    protected $casts = [
        'date' => 'date',
        'tracklist' => 'array',
        'mini' => 'boolean',
        'best' => 'boolean',
        'special' => 'boolean',
    ];

    public function artist()
    {
        return $this->belongsTo(UserArtist::class, 'user_artist_id');
    }

    // 管理画面で公式と同じ名前で扱えるようにする別名（artist_id = user_artist_id）
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

    // 収録曲の UserSong（tracklist の並び順）
    public function trackSongs()
    {
        $ids = collect($this->tracklist ?? [])->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $songs = UserSong::whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(fn ($id) => $songs->get($id))->filter()->values();
    }

    // 同じアーティストの中で発売日の順に数えた番号（id => 番号）。公式は番号を手で入れるが、マイページは発売日の順で自動で数える。
    // 種類ごとに別々に数え、番号を付けないもの（EP・ベストアルバム・企画盤）は入れない。発売日が無いものは最後
    public static function ordinalNumbers(int $userArtistId): array
    {
        $kindOf = fn ($disc) => ($disc->best || $disc->special) ? null : ($disc->mini ? 'mini' : 'album');
        $numbers = [];
        $counts = [];
        foreach (static::where('user_artist_id', $userArtistId)->orderByRaw('date IS NULL')->orderBy('date')->orderBy('id')->get() as $disc) {
            $kind = $kindOf($disc);
            if ($kind === null) {
                continue;
            }
            $counts[$kind] = ($counts[$kind] ?? 0) + 1;
            // 管理画面で番号を入れていれば、その番号（公式の album_id と同じ）
            $numbers[$disc->id] = filled($disc->album_id) ? (int) $disc->album_id : $counts[$kind];
        }
        return $numbers;
    }
}
