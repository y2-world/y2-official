<?php

namespace App\Http\Controllers;

use App\Models\SlSong;
use App\Models\SlSetlist;
use Illuminate\Http\Request;

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
        $song = SlSong::findOrFail($id);
        $title = $song->title;

        // この曲が含まれるセットリストを検索
        $setlists = SlSetlist::all()->filter(function ($setlist) use ($id, $title) {
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
        $previous = SlSong::where('artist_id', $song->artist_id)->where('id', '<', $song->id)->orderBy('id', 'desc')->first();
        $next = SlSong::where('artist_id', $song->artist_id)->where('id', '>', $song->id)->orderBy('id')->first();

        // 検索候補（曲名 + アーティスト名）
        $suggestions = SlSong::query()
            ->leftJoin('artists', 'artists.id', '=', 'sl_songs.artist_id')
            ->orderBy('sl_songs.title', 'asc')
            ->get([
                'sl_songs.id as id',
                'sl_songs.title as title',
                'artists.name as artist_name',
            ])
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => $row->title,
                    'artist_name' => $row->artist_name ?? '',
                ];
            })
            ->toArray();

        return view('sl_songs.show', compact(
            'song',
            'setlists',
            'suggestions',
            'previous',
            'next'
        ));
    }

    public function search(Request $request)
    {
        try {
            $query = $request->input('q', '');
            $rawQuery = $request->input('q', '');
            
            // デバッグ: リクエスト情報をログに記録
            \Log::info('=== SlSong Search API Called ===');
            \Log::info('Raw query: "' . $rawQuery . '"');
            \Log::info('Query length: ' . mb_strlen($rawQuery));
            \Log::info('Request method: ' . $request->method());
            \Log::info('Request URL: ' . $request->fullUrl());
            \Log::info('Request IP: ' . $request->ip());
            
            // クエリ文字列をトリム
            $query = trim($query);

            // 空文字列の場合はA-Z順に最初の10件を返す
            if ($query === '') {
                \Log::info('Query is empty, returning songs in alphabetical order');
                $songs = SlSong::query()
                    ->leftJoin('artists', 'artists.id', '=', 'sl_songs.artist_id')
                    ->orderBy('sl_songs.title')
                    ->limit(10)
                    ->get([
                        'sl_songs.id as id',
                        'sl_songs.title as title',
                        'artists.name as artist',
                    ]);
                return response()->json($songs->toArray());
            }

            // 前方一致検索（大文字小文字を区別しない）
            // エスケープ処理を追加
            $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $query);
            \Log::info('Escaped query: "' . $escapedQuery . '"');
            \Log::info('Search pattern: "' . $escapedQuery . '%"');

            $songs = SlSong::query()
                ->leftJoin('artists', 'artists.id', '=', 'sl_songs.artist_id')
                ->whereRaw('LOWER(setlist_songs.title) LIKE LOWER(?)', [$escapedQuery . '%'])
                ->orderBy('sl_songs.title')
                ->limit(10)
                ->get([
                    'sl_songs.id as id',
                    'sl_songs.title as title',
                    'artists.name as artist',
                ]);

            // デバッグ: 検索結果をログに記録
            \Log::info('Search results count: ' . $songs->count());
            if ($songs->count() > 0) {
                \Log::info('First 3 results:');
                foreach ($songs->take(3) as $index => $song) {
                    \Log::info('  [' . ($index + 1) . '] ID: ' . $song->id . ', Title: "' . $song->title . '", Artist: "' . ($song->artist ?? 'null') . '"');
                }
            } else {
                \Log::info('No results found');
            }
            
            $response = $songs->toArray();
            \Log::info('Response JSON length: ' . strlen(json_encode($response)) . ' bytes');
            \Log::info('=== SlSong Search API End ===');

            // JSON形式で返す（配列形式）
            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('=== SlSong Search API Error ===');
            \Log::error('Error message: ' . $e->getMessage());
            \Log::error('Error file: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('=== SlSong Search API Error End ===');
            return response()->json([]);
        }
    }
}
