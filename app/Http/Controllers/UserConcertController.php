<?php

namespace App\Http\Controllers;

use App\Models\UserArtist;
use App\Models\UserConcert;
use App\Models\UserSetlist;
use App\Models\UserSong;
use App\Models\UserSetlistRow;
use App\Support\SetlistSummaries;
use Illuminate\Http\Request;

/**
 * ユーザー登録アーティストの「ツアー・ライブ情報」（誰でも閲覧できる）。
 * 公式アーティストのDbConcertController相当だが、schedule/venue/text等の
 * 列を持たないユーザー登録データ向けに、必要な項目だけのシンプルな構成にしている。
 */
class UserConcertController extends Controller
{
    public function index($artistId)
    {
        $artist = UserArtist::visibleTo()->findOrFail($artistId);

        // 公式の Database のライブ一覧と同じ ?type=（1 Live = ツアーと単発ライブ、6 Tours、5 単発ライブ、2 Events）。種類の意味も公式と同じ
        $type = request()->input('type');
        $types = DbConcertController::listTypes($type);
        $tours = UserConcert::where('user_artist_id', $artistId)
            ->when($types, fn ($q) => $q->whereIn('type', $types))
            ->orderByDesc('date1')
            ->orderByDesc('id')
            ->get();

        return view('mypage.user_concerts.index', compact('artist', 'tours', 'type'));
    }

    // ライブのページ。公式の Database のライブのページ（db_concerts.show）と同じ画面で、リンク先だけマイページのものにする。
    // 参加登録の導線は持たない（mypage.attendances.setlists は登録フローの一部で「選択」「新規パターン追加」ボタンを持つため別画面）
    public function show(Request $request, $id)
    {
        $tours = UserConcert::findOrFail($id);
        $artist = $tours->artist;
        abort_unless($artist?->isVisibleTo(), 404);

        // 前へ・次へを、一覧の絞り込み（from=type-N）や、曲のページ（from=song-{曲ID}）から来たときはその範囲の中で動かす（公式と同じ）
        $from = $request->query('from');
        $tab = $request->query('tab');
        $songTourIds = null;
        if (preg_match('/^song-(\d+)$/', (string) $from, $m) && ($fromSong = UserSong::where('user_artist_id', $tours->user_artist_id)->find($m[1]))) {
            $songTourIds = UserSetlist::whereIn('user_concert_id', UserConcert::where('user_artist_id', $tours->user_artist_id)->select('id'))->get()
                ->filter(fn ($s) => collect(array_merge($s->setlist ?? [], $s->encore ?? []))->contains(fn ($i) => (string) ($i['song'] ?? '') === (string) $fromSong->id))
                ->pluck('user_concert_id')->unique()->values()->all();
        }
        $listTypes = preg_match('/^type-(\d+)$/', (string) $from, $m) ? DbConcertController::listTypes($m[1]) : null;
        $scopeQuery = function ($query) use ($from, $tours, $songTourIds, $listTypes) {
            if ($listTypes !== null) {
                $query->whereIn('type', $listTypes);
            } elseif ($from === 'type') {
                $query->where('type', $tours->type);
            } elseif ($songTourIds !== null) {
                $query->whereIn('id', $songTourIds);
            }
            return $query;
        };

        $songs = UserSong::where('user_artist_id', $tours->user_artist_id)->orderBy('sort_order', 'asc')->get();
        $tourSetlists = UserSetlist::where('user_concert_id', $id)->orderBy('order_no', 'asc')->get();
        $summaryRowTitles = UserSetlistRow::where('user_concert_id', $id)->orderBy('order_no')->get()
            ->groupBy('row')->map(fn ($rows) => $rows->pluck('title')->unique()->values());
        ['setlistSummaries' => $setlistSummaries, 'summaryRows' => $summaryRows, 'summaryPickedPatterns' => $summaryPickedPatterns]
            = SetlistSummaries::build($tourSetlists, $songs);

        $previous = $scopeQuery(UserConcert::where('user_artist_id', $tours->user_artist_id)
            ->where(function ($q) use ($tours) {
                $q->where('date1', '<', $tours->date1)
                  ->orWhere(fn ($q2) => $q2->where('date1', $tours->date1)->where('id', '<', $tours->id));
            }))->orderBy('date1', 'desc')->orderBy('id', 'desc')->first();
        $next = $scopeQuery(UserConcert::where('user_artist_id', $tours->user_artist_id)
            ->where(function ($q) use ($tours) {
                $q->where('date1', '>', $tours->date1)
                  ->orWhere(fn ($q2) => $q2->where('date1', $tours->date1)->where('id', '>', $tours->id));
            }))->orderBy('date1')->orderBy('id')->first();

        $summaryMode = $tab === 'summary';
        if ($summaryMode && $setlistSummaries->isEmpty()) {
            $tab = null;
        }

        // 曲のページのリンク。表記が曲名と違うときだけ ?title=（公式の DbSong::showUrl と同じ）
        $songTitles = $songs->pluck('title', 'id');
        $songUrl = fn ($songId, $title = null) => route('mypage.user_songs.show', array_filter([
            'id' => $songId,
            'title' => filled($title) && trim($title) !== ($songTitles[$songId] ?? null) ? trim($title) : null,
        ]));

        return view('db_concerts.show', compact('songs', 'previous', 'next', 'summaryMode', 'tours', 'tourSetlists', 'artist', 'setlistSummaries', 'summaryRows', 'from', 'tab', 'summaryRowTitles', 'summaryPickedPatterns', 'songUrl') + [
            'liveUrl' => fn ($tourId) => route('mypage.user_concerts.show', $tourId),
            'artistUrl' => route('mypage.user_artists.show', $artist->id),
            'breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $artist->name, 'url' => route('mypage.user_artists.show', $artist->id)],
                ['label' => 'Live', 'url' => route('mypage.user_artists.live', $artist->id)],
                ['label' => $tours->title],
            ],
            'songLinkResolver' => fn ($song, $alternativeTitle = '') => $songUrl($song->id, $alternativeTitle),
            'encoreArtistId' => null,
            'songPathPrefix' => '/mypage/songs/',
        ]);
    }
}
