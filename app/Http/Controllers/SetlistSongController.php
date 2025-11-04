<?php

namespace App\Http\Controllers;

use App\Models\SetlistSong;
use App\Models\Setlist;

class SetlistSongController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $song = SetlistSong::findOrFail($id);
        $title = $song->title;

        // この曲が含まれるセットリストを検索
        $setlists = Setlist::all()->filter(function ($setlist) use ($id, $title) {
            // setlist フィールドをチェック
            $setlistArr = is_array($setlist->setlist)
                ? $setlist->setlist
                : json_decode($setlist->setlist ?? '[]', true);

            // encore フィールドをチェック
            $encoreArr = is_array($setlist->encore)
                ? $setlist->encore
                : json_decode($setlist->encore ?? '[]', true);

            // fes_setlist フィールドをチェック
            $fesSetlistArr = is_array($setlist->fes_setlist)
                ? $setlist->fes_setlist
                : json_decode($setlist->fes_setlist ?? '[]', true);

            // fes_encore フィールドをチェック
            $fesEncoreArr = is_array($setlist->fes_encore)
                ? $setlist->fes_encore
                : json_decode($setlist->fes_encore ?? '[]', true);

            $allLists = array_merge($setlistArr, $encoreArr, $fesSetlistArr, $fesEncoreArr);

            foreach ($allLists as $entry) {
                // 数値IDの場合：SetlistSongのIDと一致するか
                if (is_numeric($entry['song'] ?? null) && (int)$entry['song'] === (int)$id) {
                    return true;
                }

                // 文字列の場合：タイトルが一致するか（例外処理）
                if (!is_numeric($entry['song'] ?? null)) {
                    $entryTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $entry['song'] ?? '');
                    if (trim($entryTitle) === $title) {
                        return true;
                    }
                }
            }
            return false;
        })
        ->sortByDesc('date')
        ->values();

        // 前後のSetlistSong
        $previous = SetlistSong::where('id', '<', $song->id)->orderBy('id', 'desc')->first();
        $next = SetlistSong::where('id', '>', $song->id)->orderBy('id')->first();

        // 検索候補（曲名 + アーティスト名）
        $suggestions = SetlistSong::query()
            ->leftJoin('artists', 'artists.id', '=', 'setlist_songs.artist_id')
            ->orderBy('setlist_songs.title', 'asc')
            ->get([
                'setlist_songs.id as id',
                'setlist_songs.title as title',
                'artists.name as artist_name',
            ])
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => $row->title,
                    'artist_name' => $row->artist_name,
                ];
            })
            ->toArray();

        return view('setlist_songs.show', compact(
            'song',
            'setlists',
            'previous',
            'next',
            'suggestions'
        ));
    }
}
