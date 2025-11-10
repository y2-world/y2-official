<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Album;
use App\Models\Single;
use App\Models\Bio;
use App\Models\Tour;
use Illuminate\Http\Request;
use App\Models\TourSetlist;

class SongController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $songs = Song::orderBy('id', 'asc')
            ->paginate(10);
        $totalCount = $songs->total();

        $albums = Album::orderBy('id', 'asc')
            ->get();
        $bios = Bio::orderBy('id', 'asc')
            ->get();

        // 検索用候補（曲名 + アーティスト名）
        $suggestions = \App\Models\SetlistSong::query()
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
                    'artist_name' => $row->artist_name ?? '',
                ];
            })
            ->toArray();

        // アーティスト一覧（検索フォーム用）
        $artists = \App\Artist::orderBy('id', 'asc')->get();

        // AJAXリクエストの場合はJSON形式で返す
        if (request()->wantsJson() || request()->ajax()) {
            $html = view('songs._list', compact('songs', 'totalCount'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $songs->nextPageUrl(),
                'current_page' => $songs->currentPage(),
                'last_page' => $songs->lastPage(),
            ]);
        }

        return view('songs.index', compact('albums', 'songs', 'bios', 'totalCount', 'suggestions', 'artists'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $songs = Song::findOrFail($id);
        $title = $songs->title;

        // 関連する TourSetlist を tour 付きで取得してフィルター
        $tourSetlists = TourSetlist::with('tour')->get()
            ->filter(function ($setlistModel) use ($id, $title) {
                $setlistArr = is_array($setlistModel->setlist)
                    ? $setlistModel->setlist
                    : json_decode($setlistModel->setlist ?? '[]', true);

                $encoreArr = is_array($setlistModel->encore)
                    ? $setlistModel->encore
                    : json_decode($setlistModel->encore ?? '[]', true);

                $lists = array_merge($setlistArr, $encoreArr);

                foreach ($lists as $entry) {
                    if (is_numeric($entry['song']) && (int)$entry['song'] === (int)$id) {
                        return true;
                    }

                    if (!is_numeric($entry['song'])) {
                        $entryTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $entry['song']);
                        if (trim($entryTitle) === $title) {
                            return true;
                        }
                    }
                }
                return false;
            })
            ->sortByDesc(function ($setlistModel) {
                // tour->start_date があるものを基準に降順ソート
                return optional($setlistModel->tour)->date1;
            })
            ->values(); // 並べ替え後にキーを振り直す

        // 関連ツアー一覧（重複除去）
        $tours = $tourSetlists->pluck('tour')->filter()->unique('id')->values();

        // その他データ
        $allSongs = Song::orderBy('id')->get();
        $albums = Album::orderBy('id')->get();
        $singles = Single::orderBy('id')->get();
        $previous = Song::where('id', '<', $songs->id)->orderBy('id', 'desc')->first();
        $next = Song::where('id', '>', $songs->id)->orderBy('id')->first();

        return view('songs.show', compact(
            'songs',
            'allSongs',
            'albums',
            'singles',
            'previous',
            'next',
            'tourSetlists',
            'tours'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function search(Request $request)
    {
        try {
            $query = $request->input('q', '');
            $rawQuery = $request->input('q', '');
            
            // デバッグ: リクエスト情報をログに記録
            \Log::info('=== Song Search API Called ===');
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
                $songs = Song::orderBy('title')
                    ->limit(10)
                    ->get()
                    ->map(function ($song) {
                        return [
                            'id' => $song->id,
                            'title' => $song->title,
                            'artist' => null,
                        ];
                    });
                return response()->json($songs->toArray());
            }

            // 前方一致検索（大文字小文字を区別しない）
            // エスケープ処理を追加
            $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $query);
            \Log::info('Escaped query: "' . $escapedQuery . '"');
            \Log::info('Search pattern: "' . $escapedQuery . '%"');

            $songs = Song::whereRaw('LOWER(title) LIKE LOWER(?)', [$escapedQuery . '%'])
                ->orderBy('title')
                ->limit(10)
                ->get()
                ->map(function ($song) {
                    return [
                        'id' => $song->id,
                        'title' => $song->title,
                        'artist' => null, // songsテーブルにはartist_idがないため
                    ];
                });

            // デバッグ: 検索結果をログに記録
            \Log::info('Search results count: ' . $songs->count());
            if ($songs->count() > 0) {
                \Log::info('First 3 results:');
                foreach ($songs->take(3) as $index => $song) {
                    \Log::info('  [' . ($index + 1) . '] ID: ' . $song['id'] . ', Title: "' . $song['title'] . '"');
                }
            } else {
                \Log::info('No results found');
            }
            
            $response = $songs->toArray();
            \Log::info('Response JSON length: ' . strlen(json_encode($response)) . ' bytes');
            \Log::info('=== Song Search API End ===');

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('=== Song Search API Error ===');
            \Log::error('Error message: ' . $e->getMessage());
            \Log::error('Error file: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('=== Song Search API Error End ===');
            return response()->json([]);
        }
    }
}
