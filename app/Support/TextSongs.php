<?php

namespace App\Support;

use App\Models\DbConcert;
use App\Models\DbSetlist;
use Illuminate\Support\Facades\Cache;

// セットリストに名前だけで入っている曲（DbSong として登録していない曲。カバー曲など）。
// 会場と同じように、登録していなくても曲の検索で出し、その曲を演奏したライブの一覧を開けるようにする
class TextSongs
{
    // 曲名から、LiveFans の「 [アーティスト名]」を外したもの
    public static function clean(string $song): string
    {
        return trim(preg_replace('/\s*\[[^\]]+\]/u', '', $song));
    }

    // [['title' => 曲名, 'artist_id' => ID], ...]（$artistId を渡さなければ、公開中のアーティスト全部）。10分だけ覚えておく
    public static function all(?int $artistId = null): array
    {
        return Cache::remember('text_songs:' . ($artistId ?? 'all'), 600, function () use ($artistId) {
            $tours = DbConcert::query()
                ->when($artistId, fn ($q) => $q->where('artist_id', $artistId))
                ->when(!$artistId, fn ($q) => $q->whereHas('artist', fn ($a) => $a->where('visible', 1)))
                ->pluck('artist_id', 'id');
            $songs = [];
            foreach (DbSetlist::whereIn('tour_id', $tours->keys())->get(['tour_id', 'setlist', 'encore']) as $setlist) {
                foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $item) {
                    $song = $item['song'] ?? null;
                    if (!is_string($song) || $song === '' || is_numeric($song)) {
                        continue;
                    }
                    $title = self::clean($song);
                    if ($title !== '') {
                        $songs[$tours[$setlist->tour_id] . "\0" . $title] = ['title' => $title, 'artist_id' => (int) $tours[$setlist->tour_id]];
                    }
                }
            }

            return array_values($songs);
        });
    }

    // その曲を演奏したパターン（ツアーの日付順）
    public static function setlists(int $artistId, string $title)
    {
        $tourIds = DbConcert::where('artist_id', $artistId)->pluck('id');

        return DbSetlist::with('tour')->whereIn('tour_id', $tourIds)->get()
            ->filter(function ($setlist) use ($title) {
                foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $item) {
                    $song = $item['song'] ?? null;
                    if (is_string($song) && !is_numeric($song) && self::clean($song) === $title) {
                        return true;
                    }
                }

                return false;
            })
            ->sortBy(fn ($setlist) => [(string) $setlist->tour?->date1, $setlist->row, $setlist->order_no])
            ->values();
    }
}
