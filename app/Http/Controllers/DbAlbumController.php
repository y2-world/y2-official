<?php

namespace App\Http\Controllers;

use App\Models\DbAlbum;
use App\Models\Artist;
use App\Models\DbSong;
use Illuminate\Http\Request;

class DbAlbumController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $albums = DbAlbum::where('artist_id', $artistId)
            ->orderBy('date', 'asc')
            ->paginate(10);
        $totalCount = $albums->total();
        $bios = $artist->years;

        if (request()->wantsJson() || request()->ajax()) {
            $html = view('db_albums._list', compact('albums', 'totalCount'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $albums->nextPageUrl(),
                'current_page' => $albums->currentPage(),
                'last_page' => $albums->lastPage(),
            ]);
        }

        return view('db_albums.index', compact('albums', 'bios', 'totalCount', 'artist'));
    }

    public function show($id)
    {
        $albums = DbAlbum::findOrFail($id);
        $artist = $albums->artist;
        $songs = DbSong::where('artist_id', $albums->artist_id)->get()->keyBy('id');
        // idは作成順であり発売順とは限らない（データ修正等でずれるとオリジナル/ベスト/ミニの並びが崩れる）ため、
        // 発売日(date)を基準に前後を判定する。同日発売の場合はidで副次的に順序を安定させる。
        $previous = DbAlbum::where('artist_id', $albums->artist_id)
            ->where(function ($q) use ($albums) {
                $q->where('date', '<', $albums->date)
                    ->orWhere(function ($q2) use ($albums) {
                        $q2->where('date', $albums->date)->where('id', '<', $albums->id);
                    });
            })
            ->orderByDesc('date')->orderByDesc('id')->first();
        $next = DbAlbum::where('artist_id', $albums->artist_id)
            ->where(function ($q) use ($albums) {
                $q->where('date', '>', $albums->date)
                    ->orWhere(function ($q2) use ($albums) {
                        $q2->where('date', $albums->date)->where('id', '>', $albums->id);
                    });
            })
            ->orderBy('date')->orderBy('id')->first();

        return view('db_albums.show', compact('songs', 'albums', 'previous', 'next', 'artist'));
    }
}
