<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbSong;
use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbConcert;
use Illuminate\Http\Request;
use App\Models\DbSetlist;
use Illuminate\Support\Facades\Auth;

class DbSongController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);

        $songs = DbSong::where('artist_id', $artistId)
            ->orderBy('sort_order', 'asc')
            ->paginate(10);
        $totalCount = $songs->total();

        $albums = DbAlbum::where('artist_id', $artistId)->orderBy('id', 'asc')->get();
        $bios = $artist->years;

        // 検索用候補（曲名 + アーティスト名）
        $suggestions = \App\Models\SlSong::query()
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

        // アーティスト一覧（検索フォーム用）
        $artists = Artist::orderBy('id', 'asc')->get();

        // AJAXリクエストの場合はJSON形式で返す
        if (request()->wantsJson() || request()->ajax()) {
            $html = view('db_songs._list', compact('songs', 'totalCount'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $songs->nextPageUrl(),
                'current_page' => $songs->currentPage(),
                'last_page' => $songs->lastPage(),
            ]);
        }

        return view('db_songs.index', compact('albums', 'songs', 'bios', 'totalCount', 'suggestions', 'artists', 'artist'));
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
    public function show(Request $request, $id)
    {
        $songs = DbSong::findOrFail($id);

        // 関連する DbSetlist を tour 付きで取得してフィルター
        $tourSetlists = $songs->performedTourSetlists();

        // 関連ツアー一覧（重複除去）
        $tours = $tourSetlists->pluck('tour')->filter()->unique('id')->values();

        // その他データ
        $allSongs = DbSong::orderBy('sort_order')->get();
        $albums = DbAlbum::orderBy('id')->get();
        $singles = DbSingle::orderBy('id')->get();
        $previous = DbSong::where('artist_id', $songs->artist_id)->where('sort_order', '<', $songs->sort_order)->orderBy('sort_order', 'desc')->first();
        $next = DbSong::where('artist_id', $songs->artist_id)->where('sort_order', '>', $songs->sort_order)->orderBy('sort_order')->first();
        $songNumber = DbSong::where('artist_id', $songs->artist_id)->where('sort_order', '<=', $songs->sort_order)->count();

        // 「Live Performances」の隣に出す2つ目のタブは、ログイン中の外部ユーザーが誰かによって
        // 決まる（Referer等の遷移元は見ない）。Yuki本人のアカウント（管理画面でis_yuki=trueに
        // 設定）なら「Yuki's Live Attendances」（セットリストサイト全体＝運営者本人の記録）、
        // それ以外の一般ユーザーなら「My Live Attendances」（自分の参加記録）。未ログインの場合は
        // どちらの参加記録も表示する意味が無いため、2つ目のタブ自体を出さずLive Performancesのみにする。
        $externalUser = Auth::guard('external')->user();
        if (!$externalUser) {
            $secondTab = null;
            $secondTabSetlists = collect();
        } elseif ($externalUser->is_yuki) {
            $secondTab = 'yuki';
            $secondTabSetlists = $songs->performedSlSetlists();
        } else {
            $secondTab = 'mine';
            $secondTabSetlists = $songs->myAttendedTours($tourSetlists);
        }

        // ログイン中（2つ目のタブが存在する）なら、まず自分（またはYuki）の参加記録を見せる。
        // Live Performancesを見ていたところからPrevious/Nextで移動した場合だけ、
        // ?tab=performances を引き継いでLive Performancesを維持する。
        $initialTab = $secondTab && $request->query('tab') !== 'performances' ? $secondTab : 'performances';

        $secondTabSongNumber = null;
        $secondTabPrevious = null;
        $secondTabNext = null;
        if ($secondTab === 'yuki') {
            // Yuki's Live Attendancesタブ用：sl_songs側の番号・前後の曲。sl_songsにはsort_orderが
            // 無く、id自体が聴いた順（登録順）を表すため、DbSong側のsort_orderとは辿らず、
            // SlSong.id順で直接前後の曲を求める（db_song_idで逆引きしたSlSongが無い＝このDbSongが
            // まだSlSongに紐付いていない場合はタブ内の曲番を出さない）。
            $mySlSong = $songs->slSongs()->first();
            if ($mySlSong) {
                $secondTabSongNumber = \App\Models\SlSong::where('artist_id', $mySlSong->artist_id)->where('id', '<=', $mySlSong->id)->count();
                $secondTabPrevious = \App\Models\SlSong::where('artist_id', $mySlSong->artist_id)->where('id', '<', $mySlSong->id)->orderBy('id', 'desc')->first();
                $secondTabNext = \App\Models\SlSong::where('artist_id', $mySlSong->artist_id)->where('id', '>', $mySlSong->id)->orderBy('id')->first();
            }
        } elseif ($secondTab === 'mine') {
            // My Live Attendancesタブ用：自分の参加記録内での初めて聴いた順・前後の曲
            // （AttendanceController::index / UserSongController::show と同じ考え方）
            $firstSeenOrder = DbSong::firstSeenOrderFor($externalUser, $songs->artist_id);
            $secondTabSongNumber = $firstSeenOrder[$songs->id] ?? null;
            $orderedSongIds = array_keys($firstSeenOrder);
            $currentIndex = array_search($songs->id, $orderedSongIds, true);
            $previousId = $currentIndex !== false && $currentIndex > 0 ? $orderedSongIds[$currentIndex - 1] : null;
            $nextId = $currentIndex !== false && $currentIndex < count($orderedSongIds) - 1 ? $orderedSongIds[$currentIndex + 1] : null;
            $secondTabPrevious = $previousId ? DbSong::find($previousId) : null;
            $secondTabNext = $nextId ? DbSong::find($nextId) : null;
        }

        return view('db_songs.show', compact(
            'songs',
            'allSongs',
            'albums',
            'singles',
            'previous',
            'next',
            'tourSetlists',
            'tours',
            'songNumber',
            'secondTab',
            'secondTabSetlists',
            'secondTabSongNumber',
            'secondTabPrevious',
            'secondTabNext',
            'initialTab'
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
            \Log::info('=== DbSong Search API Called ===');
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
                $songs = \App\Support\JapaneseNameSorter::sortBy(DbSong::all(), 'title')
                    ->take(10)
                    ->values()
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

            $songs = \App\Support\JapaneseNameSorter::sortBy(
                DbSong::whereRaw('LOWER(title) LIKE LOWER(?)', [$escapedQuery . '%'])->get(),
                'title'
            )
                ->take(10)
                ->values()
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
            \Log::info('=== DbSong Search API End ===');

            return response()->json($response);
        } catch (\Exception $e) {
            \Log::error('=== DbSong Search API Error ===');
            \Log::error('Error message: ' . $e->getMessage());
            \Log::error('Error file: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('=== DbSong Search API Error End ===');
            return response()->json([]);
        }
    }
}
