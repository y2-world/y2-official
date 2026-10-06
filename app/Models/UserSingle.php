<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// マイページで作ったアーティストのシングル（EP を含む）。公式の DbSingle と同じ持ち方
class UserSingle extends Model
{
    protected $fillable = [
        'user_artist_id',
        'external_user_id',
        'title',
        'date',
        'ep',
        'tracklist',
    ];

    protected $casts = [
        'date' => 'date',
        'tracklist' => 'array',
        'ep' => 'boolean',
    ];

    public function artist()
    {
        return $this->belongsTo(UserArtist::class, 'user_artist_id');
    }

    // 収録曲の UserSong（tracklist の並び順）
    public function trackSongs()
    {
        $ids = collect($this->tracklist ?? [])->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $songs = UserSong::whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(fn ($id) => $songs->get($id))->filter()->values();
    }

    // 同じアーティストの中で発売日の順に数えた番号（id => 番号）。公式は番号を手で入れるが、マイページは発売日の順で自動で数える。
    // 種類ごとに別々に数え、番号を付けないもの（EP・ベストアルバム）は入れない。発売日が無いものは最後
    public static function ordinalNumbers(int $userArtistId): array
    {
        $kindOf = fn ($disc) => $disc->ep ? null : 'single';
        $numbers = [];
        $counts = [];
        foreach (static::where('user_artist_id', $userArtistId)->orderByRaw('date IS NULL')->orderBy('date')->orderBy('id')->get() as $disc) {
            $kind = $kindOf($disc);
            if ($kind === null) {
                continue;
            }
            $counts[$kind] = ($counts[$kind] ?? 0) + 1;
            $numbers[$disc->id] = $counts[$kind];
        }
        return $numbers;
    }
}
