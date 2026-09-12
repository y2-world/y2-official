<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Models\SlSetlist;
use App\Models\SlSong;

trait ComputesDbSongStamps
{
    // B'zのセットリストには稲葉浩志のソロ曲が含まれることがあるため（DbSetlistResource参照）、
    // 稲葉浩志の集計時はB'zのツアーのセットリストも走査対象に含める。
    // 曲自体の帰属（db_songs.artist_id）で最終的に絞り込むので、B'z側の集計に
    // 稲葉浩志の曲が混ざったり、その逆が起きたりはしない。
    private const BZ_ARTIST_ID = 3;
    private const INABA_ARTIST_ID = 39;

    private function crossoverTourArtistIds(int $artistId): array
    {
        if ($artistId === self::INABA_ARTIST_ID) {
            return [self::INABA_ARTIST_ID, self::BZ_ARTIST_ID];
        }

        return [$artistId];
    }

    // 公式ツアー記録（db_setlists）上、一度でも演奏された曲IDを集める。
    // ここに含まれない曲は「ライブでそもそも未演奏」として台紙自体をグレー表示する。
    // Yuki本人の記録（StatsController）・外部ユーザーの記録（MyPageStatsController）双方の
    // スタンプ帳で共通して使う、そのアーティスト単位の計算。
    private function everPerformedDbSongIds(int $artistId): array
    {
        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $tourIds = DbConcert::whereIn('artist_id', $this->crossoverTourArtistIds($artistId))->pluck('id');
        $tourSetlists = DbSetlist::whereIn('tour_id', $tourIds)->get();

        $everPerformedDbSongIds = [];
        foreach ($tourSetlists as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song']) && ($songArtistIds[(int)$s['song']] ?? null) === $artistId) {
                    $everPerformedDbSongIds[(int)$s['song']] = true;
                }
            }
        }

        return $everPerformedDbSongIds;
    }

    // Yuki本人が実際にライブで演奏した記録（SlSetlist、フェスのゲスト出演含む）がある
    // db_song_idの集合を、アーティストIDに関わらず一度に集める。
    // Live Stamp Bookの「アーティストごとのUnique Songs」集計で、全アーティストを
    // 都度SlSetlist全件走査するのを避けるための共通ヘルパー。
    private function playedDbSongIdsByArtist(): array
    {
        $today = now()->toDateString();
        $playedSlSongIds = [];
        $setlists = SlSetlist::where('date', '<=', $today)->get();

        foreach ($setlists as $setlist) {
            $allSongs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? [],
                $this->flattenFesSongs($setlist->fes_setlist ?? []),
                $this->flattenFesSongs($setlist->fes_encore ?? [])
            );

            foreach ($allSongs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $playedSlSongIds[(int)$songData['song']] = true;
                }
            }
        }

        $playedDbSongIdsByArtist = [];
        SlSong::whereNotNull('db_song_id')->select('id', 'artist_id', 'db_song_id')
            ->get()
            ->each(function (SlSong $slSong) use ($playedSlSongIds, &$playedDbSongIdsByArtist) {
                if (isset($playedSlSongIds[$slSong->id])) {
                    $playedDbSongIdsByArtist[$slSong->artist_id][(int)$slSong->db_song_id] = true;
                }
            });

        return $playedDbSongIdsByArtist;
    }
}
