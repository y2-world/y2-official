<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\ExternalUser;
use App\Models\SlSetlist;
use App\Models\SlSong;

// Yuki 本人のアカウントの参加登録で、Yuki's Setlists の公演から選ぶための一覧。マイページにまだ参加記録が無い日の公演を、新しい順に。
// 公演ごと（フェスは出演したアーティストごと）に、Database のライブとその日のパターンを当てはめる：
// ライブは Yuki's Setlists に付いている Database のライブ（db_concert_id）、無ければそのアーティストのその日を含むライブ。
// パターンはスケジュールから選んだ公演日（schedule_dates）にその日があるもの、どのパターンにも公演日が無ければ全部（選ぶ）。
// 日替わりの曲は、Yuki's Setlists の曲目と同じ曲（Database の曲）を選んでおく
class YukiSetlistCandidates
{
    /**
     * @return array{matched: array<int, array>, unmatched: array<int, array>}
     *   matched：['date', 'title', 'artist', 'venue', 'tour' => DbConcert, 'patterns' => [['pattern' => DbSetlist, 'daily' => [_uuid, ...]], ...]]
     *   unmatched：['date', 'title', 'artist']（Database のライブ・パターンが見つからない公演）
     */
    public static function for(ExternalUser $yuki): array
    {
        $registeredDates = $yuki->attendances()->whereNotNull('attended_date')->pluck('attended_date')
            ->map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('Y-m-d'))->flip();
        $slToDbSong = SlSong::whereNotNull('db_song_id')->pluck('db_song_id', 'id');
        $artists = Artist::pluck('name', 'id');
        $matched = $unmatched = [];
        foreach (SlSetlist::where('date', '<=', now()->toDateString())->orderByDesc('date')->orderByDesc('id')->get() as $setlist) {
            $date = substr((string) $setlist->date, 0, 10);
            if (isset($registeredDates[$date])) {
                continue;
            }
            foreach (static::stages($setlist) as $artistId => $items) {
                $entry = ['date' => $date, 'title' => $setlist->title, 'artist' => $artists[$artistId] ?? ''];
                $tour = ($setlist->db_concert_id && !$setlist->fes ? DbConcert::find($setlist->db_concert_id) : null)
                    ?? DbConcert::where('artist_id', $artistId)->whereDate('date1', '<=', $date)
                        ->where(fn ($q) => $q->whereDate('date2', '>=', $date)->orWhere(fn ($q2) => $q2->whereNull('date2')->whereDate('date1', $date)))
                        ->orderByDesc('date1')->first();
                $patterns = $tour ? static::patternsOn($tour, $date) : collect();
                if ($patterns->isEmpty()) {
                    $unmatched[] = $entry;
                    continue;
                }
                $heardDbSongIds = collect($items)->pluck('song')->filter(fn ($song) => is_numeric($song))
                    ->map(fn ($song) => $slToDbSong[(int) $song] ?? null)->filter()->map(fn ($id) => (string) $id)->flip();
                // 会場：その日の Database のスケジュールの会場、無ければ Yuki's Setlists の会場
                $scheduled = collect($tour->parseScheduleEntries())->firstWhere('date', $date);
                $venue = trim((string) ($scheduled['venue'] ?? '')) ?: trim((string) $setlist->venue);
                $matched[] = $entry + ['venue' => $venue, 'tour' => $tour, 'patterns' => $patterns->map(fn ($pattern) => [
                    'pattern' => $pattern,
                    'daily' => static::dailySelection($pattern, $heardDbSongIds),
                ])->values()->all()];
            }
        }

        return compact('matched', 'unmatched');
    }

    // その公演の出演アーティストと、その曲（[アーティストの id => 曲目]）。フェスはステージごと
    private static function stages(SlSetlist $setlist): array
    {
        if (!$setlist->fes) {
            return $setlist->artist_id ? [(int) $setlist->artist_id => array_merge($setlist->setlist ?? [], $setlist->encore ?? [])] : [];
        }
        $stages = [];
        foreach (array_merge((array) ($setlist->fes_setlist ?? []), (array) ($setlist->fes_encore ?? [])) as $block) {
            if (($block['type'] ?? 'song') === 'block' && is_numeric($block['artist'] ?? null)) {
                $stages[(int) $block['artist']] = array_merge($stages[(int) $block['artist']] ?? [], (array) ($block['songs'] ?? []));
            }
        }

        return $stages;
    }

    // その日のパターン。公演日（schedule_dates）にその日があるパターン。公演日のあるパターンが1つも当たらなければ、公演日の無いパターン
    private static function patternsOn(DbConcert $tour, string $date)
    {
        $patterns = DbSetlist::where('tour_id', $tour->id)->orderBy('row')->orderBy('order_no')->get();
        $dated = $patterns->filter(fn ($pattern) => collect((array) ($pattern->schedule_dates ?? []))->contains(fn ($key) => str_starts_with((string) $key, $date)));

        return $dated->isNotEmpty() ? $dated->values() : $patterns->filter(fn ($pattern) => empty($pattern->schedule_dates))->values();
    }

    // 日替わりの塊（候補2曲以上）で、Yuki's Setlists の曲目にある曲を選ぶ（参加登録の日替わりの曲の選択と同じ _uuid の並び）。
    // 候補1曲の塊は、参加登録と同じく自動で選ぶ
    private static function dailySelection(DbSetlist $pattern, $heardDbSongIds): array
    {
        $selected = [];
        foreach ([(array) ($pattern->setlist ?? []), (array) ($pattern->encore ?? [])] as $items) {
            foreach (groupDailySongClusters($items) as $cluster) {
                $items = $cluster['items'];
                if (count($items) <= 1) {
                    if (isset($items[0]['_uuid'])) $selected[] = $items[0]['_uuid'];
                    continue;
                }
                foreach ($items as $item) {
                    if (isset($item['_uuid']) && isset($heardDbSongIds[(string) ($item['song'] ?? '')])) {
                        $selected[] = $item['_uuid'];
                    }
                }
            }
        }

        return $selected;
    }
}
