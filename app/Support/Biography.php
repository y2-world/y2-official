<?php

namespace App\Support;

// アーティストの Biography（公式の Database とマイページで共通）。手で書いた文章は無く、シングル・アルバム・ツアーから作る。
// 公式は DbSingle・DbAlbum・DbSong・DbConcert（artist_id）、マイページは UserSingle・UserAlbum・UserSong・UserConcert（user_artist_id）
class Biography
{
    private const SETS = [
        'official' => ['single' => \App\Models\DbSingle::class, 'album' => \App\Models\DbAlbum::class, 'song' => \App\Models\DbSong::class, 'concert' => \App\Models\DbConcert::class, 'artist' => 'artist_id'],
        'user' => ['single' => \App\Models\UserSingle::class, 'album' => \App\Models\UserAlbum::class, 'song' => \App\Models\UserSong::class, 'concert' => \App\Models\UserConcert::class, 'artist' => 'user_artist_id'],
    ];

    // 年の一覧（シングル・アルバムの発売年と、ツアーの年）。[(object)['year' => 2026], ...]
    public static function years(string $kind, int $artistId)
    {
        $set = self::SETS[$kind];
        $discYears = collect([$set['single'], $set['album']])->flatMap(fn ($model) => $model::where($set['artist'], $artistId)->whereNotNull('date')->pluck('date')
            ->map(fn ($d) => (int) date('Y', strtotime($d))));
        $tourYears = $set['concert']::where($set['artist'], $artistId)->whereNotNull('date1')->get(['date1', 'date2'])
            ->flatMap(fn ($t) => array_filter([(int) date('Y', strtotime($t->date1)), $t->date2 ? (int) date('Y', strtotime($t->date2)) : null]));

        return collect($discYears)->merge($tourYears)->unique()->sort()->values()->map(fn ($y) => (object) ['year' => $y]);
    }

    // その年のページ：その年に初めてシングル・アルバムに入った曲と、その年のツアー。['songs' => ..., 'tours' => ...]
    public static function year(string $kind, int $artistId, int $year): array
    {
        $set = self::SETS[$kind];
        $ids = fn ($disc) => collect($disc->tracklist ?? [])->pluck('id')->filter()->map(fn ($id) => (string) $id);
        // 前の年までに、シングル・アルバム（ベスト盤を除く）に入っていた曲
        $previousIds = $set['single']::where($set['artist'], $artistId)->whereYear('date', '<', $year)->get()->flatMap($ids)
            ->merge($set['album']::where($set['artist'], $artistId)->where('best', false)->whereYear('date', '<', $year)->get()->flatMap($ids))
            ->unique();
        // その年のシングル・アルバム（ベスト盤も）の曲のうち、初めて出たもの
        $songIds = $set['single']::where($set['artist'], $artistId)->whereYear('date', $year)->get()->flatMap($ids)
            ->merge($set['album']::where($set['artist'], $artistId)->whereYear('date', $year)->get()->flatMap($ids))
            ->diff($previousIds)->unique()->values();
        $songs = $set['song']::where($set['artist'], $artistId)->whereIn('id', $songIds)->orderBy('sort_order')->get();
        $tours = $set['concert']::where($set['artist'], $artistId)
            ->where(fn ($q) => $q->whereYear('date1', $year)->orWhereYear('date2', $year))
            ->orderBy('date1')->get();

        return ['songs' => $songs, 'tours' => $tours];
    }
}
