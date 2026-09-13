<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SplitsKindRef;
use App\Models\Artist;
use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Models\ExternalUserAttendance;
use App\Models\UserArtist;
use App\Models\UserConcert;
use App\Models\UserSetlist;
use App\Models\UserSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * My Pageの出席（セットリスト）登録フロー。
 *
 * データベースは「公式」（Filament管理画面で登録、artists/db_concerts/db_setlists）と
 * 「ユーザー登録」（My Pageで誰かが追加、user_artists/user_concerts/user_setlists）の
 * 2系統に分かれている。ユーザー登録データは誰が登録したかに関わらず全ユーザーが閲覧・追加できる
 * （公式データと同じ扱い。ただし公式アーティスト・公式ツアーへの新規ツアー/セットリストパターン追加は
 * My Pageからは行えない）。テーブルが違うだけで役割は同じなので、URL上はどちらも
 * "official-{id}" / "user-{id}" という文字列で区別する（新規作成前は "new"）。
 */
class AttendanceController extends Controller
{
    use SplitsKindRef;

    public function index(Request $request)
    {
        $query = Auth::guard('external')->user()
            ->attendances()
            ->with(['dbSetlist.tour.artist', 'userSetlist.concert.artist']);

        $artistId = $request->input('artist_id'); // "official-{id}" or "user-{id}"
        $filterArtist = null;
        if ($artistId) {
            [$kind, $id] = $this->splitRef($artistId);
            if ($kind === 'official') {
                // type=4（ソロ）は本人単独のプロジェクトであり、アーティスト本体のライブ履歴には含めない
                $query->whereHas('dbSetlist.tour', fn ($q) => $q->where('artist_id', $id)->where('type', '!=', 4));
                $filterArtist = Artist::find($id);
            } else {
                $query->whereHas('userSetlist.concert', fn ($q) => $q->where('user_artist_id', $id));
                $filterArtist = UserArtist::find($id);
            }
        }

        $year = $request->input('year');
        if ($year) {
            $query->whereYear('attended_date', $year);
        }

        $songId = $request->input('song_id'); // "official-{id}" or "user-{id}"
        $song = null;
        $songKind = null;
        $songNumber = null;
        $previousSong = null;
        $nextSong = null;
        if ($songId) {
            [$songKind, $songIdValue] = $this->splitRef($songId);
            $song = $songKind === 'official' ? DbSong::find($songIdValue) : UserSong::find($songIdValue);

            if ($song) {
                // 自分の参加記録を古い順に見ていったとき、そのアーティストの曲の中で
                // 何番目に初めて自分のリストに登場したかを#として表示する
                if ($songKind === 'official') {
                    $orderedAttendances = Auth::guard('external')->user()
                        ->attendances()
                        ->whereHas('dbSetlist.tour', fn ($q) => $q->where('artist_id', $song->artist_id))
                        ->with('dbSetlist')
                        ->orderBy('attended_date')
                        ->get();
                    $songKey = fn ($setlist) => array_merge($setlist->setlist ?? [], $setlist->encore ?? []);
                } else {
                    $orderedAttendances = Auth::guard('external')->user()
                        ->attendances()
                        ->whereHas('userSetlist.concert', fn ($q) => $q->where('user_artist_id', $song->user_artist_id))
                        ->with('userSetlist')
                        ->orderBy('attended_date')
                        ->get();
                    $songKey = fn ($setlist) => array_merge($setlist->setlist ?? [], $setlist->encore ?? []);
                }

                $firstSeenOrder = [];
                foreach ($orderedAttendances as $attendance) {
                    $setlist = $songKind === 'official' ? $attendance->dbSetlist : $attendance->userSetlist;
                    if (!$setlist) {
                        continue;
                    }
                    foreach ($songKey($setlist) as $s) {
                        if (isset($s['song']) && is_numeric($s['song'])) {
                            $sid = (int) $s['song'];
                            if (!isset($firstSeenOrder[$sid])) {
                                $firstSeenOrder[$sid] = count($firstSeenOrder) + 1;
                            }
                        }
                    }
                }

                $songNumber = $firstSeenOrder[(int) $songIdValue] ?? null;

                // 初めて聴いた順で前後の曲を求める
                $orderedSongIds = array_keys($firstSeenOrder);
                $currentIndex = array_search((int) $songIdValue, $orderedSongIds, true);
                $previousSongId = $currentIndex !== false && $currentIndex > 0 ? $orderedSongIds[$currentIndex - 1] : null;
                $nextSongId = $currentIndex !== false && $currentIndex < count($orderedSongIds) - 1 ? $orderedSongIds[$currentIndex + 1] : null;
                $songModel = $songKind === 'official' ? DbSong::class : UserSong::class;
                $previousSong = $previousSongId ? $songModel::find($previousSongId) : null;
                $nextSong = $nextSongId ? $songModel::find($nextSongId) : null;
            }

            if ($songKind === 'official') {
                $matchingSetlistIds = DbSetlist::all()->filter(function (DbSetlist $setlist) use ($songIdValue) {
                    foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                        if (isset($s['song']) && (int) $s['song'] === (int) $songIdValue) {
                            return true;
                        }
                    }
                    return false;
                })->pluck('id');
                $query->whereIn('db_setlist_id', $matchingSetlistIds);
            } else {
                $matchingSetlistIds = UserSetlist::all()->filter(function (UserSetlist $setlist) use ($songIdValue) {
                    foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                        if (isset($s['song']) && (int) $s['song'] === (int) $songIdValue) {
                            return true;
                        }
                    }
                    return false;
                })->pluck('id');
                $query->whereIn('user_setlist_id', $matchingSetlistIds);
            }
        }

        if ($song) {
            $query->orderByDesc('attended_date');
        } else {
            $query->orderBy('attended_date');
        }
        $attendances = $query->get();

        $userId = Auth::guard('external')->id();

        $officialArtists = Artist::whereHas('tours', function ($q) use ($userId) {
            $q->whereHas('tourSetlists', function ($q2) use ($userId) {
                $q2->whereHas('attendances', function ($q3) use ($userId) {
                    $q3->where('external_user_id', $userId);
                });
            });
        })->orderBy('name')->get();

        $myArtists = UserArtist::whereHas('concerts', function ($q) use ($userId) {
                $q->whereHas('setlists', function ($q2) use ($userId) {
                    $q2->whereHas('attendances', function ($q3) use ($userId) {
                        $q3->where('external_user_id', $userId);
                    });
                });
            })
            ->orderBy('name')
            ->get();

        $years = Auth::guard('external')->user()
            ->attendances()
            ->whereNotNull('attended_date')
            ->get()
            ->pluck('attended_date')
            ->map(fn ($date) => $date->format('Y'))
            ->unique()
            ->sort()
            ->values();

        return view('mypage.attendances.index', compact(
            'attendances', 'officialArtists', 'myArtists', 'artistId', 'song', 'songKind', 'songNumber', 'filterArtist', 'years', 'year', 'previousSong', 'nextSong'
        ));
    }

    // ステップ1: アーティストを選ぶ
    // 公式（Filament管理画面で登録）と、My Pageでユーザーが追加したアーティストを分けて表示する。
    // ユーザー登録アーティストは誰が登録したかに関わらず全ユーザーが閲覧・選択できる。
    public function create()
    {
        $officialArtists = Artist::whereHas('tours')->orderBy('name')->get();
        $myArtists = UserArtist::orderBy('name')->get();

        return view('mypage.attendances.create', compact('officialArtists', 'myArtists'));
    }

    // 一覧に無いアーティスト名を入力し、そのままツアー選択画面へ進む。
    // この時点ではDBに何も保存しない（最後の参加日登録まで確定させない）。
    // 名前はクエリparamで次画面に引き継ぐ。
    public function newArtist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        return redirect()->route('mypage.attendances.tours', ['artistId' => 'new', 'name' => $request->input('name')]);
    }

    // ステップ2: そのアーティストのツアーを選ぶ
    // artistId は "official-{id}" / "user-{id}" / "new"。
    // ユーザー登録アーティストは誰が登録したかに関わらず、全ユーザーがツアーを閲覧・追加できる。
    public function tours(Request $request, $artistId)
    {
        if ($artistId === 'new') {
            $artist = null;
            $artistName = $request->query('name', '');
            abort_if($artistName === '', 404);
            $tours = collect();
            $canAddTour = true;
        } else {
            [$kind, $id] = $this->splitRef($artistId);

            if ($kind === 'official') {
                $artist = Artist::findOrFail($id);
                $artistName = $artist->name;
                $tours = DbConcert::where('artist_id', $id)->orderBy('date1', 'desc')->get();
                // 公式アーティストは、Yuki本人が管理画面から正式にツアーを登録する対象であり、
                // My Page利用者が勝手にツアーを追加すべきではない
                $canAddTour = false;
            } else {
                $artist = UserArtist::findOrFail($id);
                $artistName = $artist->name;
                $tours = UserConcert::where('user_artist_id', $id)->orderBy('date1', 'desc')->get();
                $canAddTour = true;
            }
        }

        return view('mypage.attendances.tours', compact('artist', 'artistId', 'artistName', 'tours', 'canAddTour'));
    }

    // 一覧に無いツアー・ライブ名を入力し、セットリスト入力画面へ進む。
    // この時点でもDBに何も保存しない。アーティスト名・ツアー名はクエリparamで次画面に引き継ぐ。
    public function newTour(Request $request, $artistId)
    {
        // 公式アーティストには、My Pageからツアーを追加できない
        if ($artistId !== 'new') {
            [$kind, $id] = $this->splitRef($artistId);
            abort_unless($kind === 'user', 403);
            UserArtist::findOrFail($id);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'date1' => ['nullable', 'date'],
            'date2' => ['nullable', 'date', 'after_or_equal:date1'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $params = ['artistId' => $artistId, 'tourId' => 'new', 'title' => $data['title']];
        if (!empty($data['date1'])) {
            $params['date1'] = $data['date1'];
        }
        if (!empty($data['date2'])) {
            $params['date2'] = $data['date2'];
        }
        if ($artistId === 'new') {
            $params['name'] = $request->query('name', $request->input('name'));
        }

        return redirect()->route('mypage.attendances.setlist_create', $params);
    }

    // ステップ3: そのツアー内のセットリストパターンを選ぶ
    // パターンが1つしかない場合は選ぶまでもないので、曲目未入力なら曲目入力画面へ、
    // 入力済みならそのまま参加日入力フォームへ進める
    public function setlists($tourId)
    {
        [$kind, $id] = $this->splitRef($tourId);

        if ($kind === 'official') {
            $tour = DbConcert::findOrFail($id);
            $tourSetlists = DbSetlist::where('tour_id', $id)->orderBy('row', 'asc')->orderBy('order_no', 'asc')->get();
        } else {
            $tour = UserConcert::findOrFail($id);
            $tourSetlists = UserSetlist::where('user_concert_id', $id)->orderBy('row', 'asc')->orderBy('order_no', 'asc')->get();
        }

        // パターンが1つしかなくても曲目未入力（空）の場合だけは、
        // プレビューする内容が無いので曲目入力画面へ直接進める
        if ($tourSetlists->count() === 1) {
            $only = $tourSetlists->first();
            if (empty($only->setlist) && empty($only->encore)) {
                $artistRef = $kind === 'official' ? 'official-' . $tour->artist_id : 'user-' . $tour->user_artist_id;
                return redirect()->route('mypage.attendances.setlist_create', ['artistId' => $artistRef, 'tourId' => $tourId]);
            }
        }

        $songs = $kind === 'official' ? DbSong::orderBy('id', 'asc')->get() : UserSong::where('user_artist_id', $tour->user_artist_id)->orderBy('id', 'asc')->get();

        return view('mypage.attendances.setlists', compact('tour', 'tourSetlists', 'songs', 'kind', 'tourId'));
    }

    // 既存ツアーに、別日程用の新しいセットリストパターンを追加する画面へ進む（未保存）
    public function newSetlistPattern($tourId)
    {
        [$kind, $id] = $this->splitRef($tourId);

        // 公式ツアーは、Yuki本人が管理画面から正式にセットリストを登録する対象であり、
        // My Page利用者が勝手にセットリストパターンを追加すべきではない
        abort_unless($kind === 'user', 403);

        $tour = UserConcert::findOrFail($id);
        $artistRef = 'user-' . $tour->user_artist_id;

        return redirect()->route('mypage.attendances.setlist_create', ['artistId' => $artistRef, 'tourId' => $tourId]);
    }

    // 新規セットリストパターンの曲目入力画面。
    // artistId/tourId が 'new' の場合は、まだ何もDBに存在しない状態。
    public function setlistCreate(Request $request, $artistId, $tourId)
    {
        $artistName = null;
        $tourTitle = null;
        $tourDate1 = null;
        $tourDate2 = null;
        $songOptions = collect();

        if ($artistId !== 'new') {
            [$artistKind, $artistDbId] = $this->splitRef($artistId);
            if ($artistKind === 'official') {
                $artist = Artist::findOrFail($artistDbId);
                $artistName = $artist->name;
                $songOptions = DbSong::where('artist_id', $artistDbId)->orderBy('id', 'asc')->pluck('title');
            } else {
                $artist = UserArtist::findOrFail($artistDbId);
                $artistName = $artist->name;
                $songOptions = UserSong::where('user_artist_id', $artistDbId)->orderBy('id', 'asc')->pluck('title');
            }
        } else {
            $artistName = $request->query('name', '');
            abort_if($artistName === '', 404);
        }

        if ($tourId !== 'new') {
            [$tourKind, $tourDbId] = $this->splitRef($tourId);
            if ($tourKind === 'official') {
                $tour = DbConcert::findOrFail($tourDbId);
                $tourTitle = $tour->title;
            } else {
                $tour = UserConcert::findOrFail($tourDbId);
                $tourTitle = $tour->title;
            }
        } else {
            $tourTitle = $request->query('title', '');
            abort_if($tourTitle === '', 404);
            $tourDate1 = $request->query('date1');
            $tourDate2 = $request->query('date2');
        }

        return view('mypage.attendances.setlist_create', compact('artistId', 'artistName', 'tourId', 'tourTitle', 'tourDate1', 'tourDate2', 'songOptions'));
    }

    // 曲目入力の次の画面（参加日・会場の入力）。まだDBには何も保存しない。
    // 入力済みの曲目はhiddenフィールドとしてこの画面が保持し、次の最終送信までそのまま引き継ぐ。
    public function setlistConfirm(Request $request, $artistId, $tourId)
    {
        $validator = Validator::make($request->all(), [
            'setlist' => ['array'],
            'setlist.*' => ['nullable', 'string', 'max:255'],
            'encore' => ['array'],
            'encore.*' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if ($artistId === 'new') {
            $artistName = $request->input('artist_name');
        } else {
            [$artistKind, $artistDbId] = $this->splitRef($artistId);
            $artistName = $artistKind === 'official' ? Artist::findOrFail($artistDbId)->name : UserArtist::findOrFail($artistDbId)->name;
        }

        if ($tourId === 'new') {
            $tourTitle = $request->input('tour_title');
            $tourDate1 = $request->input('tour_date1');
            $tourDate2 = $request->input('tour_date2');
        } else {
            [$tourKind, $tourDbId] = $this->splitRef($tourId);
            $tourTitle = $tourKind === 'official' ? DbConcert::findOrFail($tourDbId)->title : UserConcert::findOrFail($tourDbId)->title;
            $tourDate1 = null;
            $tourDate2 = null;
        }

        $setlist = array_values(array_filter($request->input('setlist', []), fn ($t) => trim((string) $t) !== ''));
        $encore = array_values(array_filter($request->input('encore', []), fn ($t) => trim((string) $t) !== ''));

        // 単発ライブで開始日のみ分かっている場合は、参加日の初期値として使う
        $defaultAttendedDate = $tourDate1 && !$tourDate2 ? $tourDate1 : null;

        return view('mypage.attendances.confirm', compact(
            'artistId', 'artistName', 'tourId', 'tourTitle', 'tourDate1', 'tourDate2', 'setlist', 'encore', 'defaultAttendedDate'
        ));
    }

    // 曲目・参加日をまとめて確定登録する。
    // ここで初めてDBへの書き込みが発生する（アーティスト・ツアー・セットリスト・出席記録を一括作成）。
    // 途中でブラウザバック等により離脱した場合、それまでの入力内容はどこにも保存されない。
    //
    // ユーザー登録データ（user_artists/user_concerts/user_setlists）は誰が作成したかに関わらず
    // 全ユーザーが追加できる。ただし公式アーティスト・公式ツアー配下への新規ツアー/パターン追加は
    // 許可しない（tours画面・newTourで既にガード済みだが、URL直叩き対策として再度ここでも守る）。
    public function setlistStore(Request $request, $artistId, $tourId)
    {
        $validator = Validator::make($request->all(), [
            'setlist' => ['array'],
            'setlist.*' => ['nullable', 'string', 'max:255'],
            'encore' => ['array'],
            'encore.*' => ['nullable', 'string', 'max:255'],
            'attended_date' => ['required', 'date'],
            'venue' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $userId = Auth::guard('external')->id();

        $attendance = DB::transaction(function () use ($request, $artistId, $tourId, $data, $userId) {
            // アーティストを確定させる（常にuser_artists側。公式アーティストへの新規ツアー追加は許可しない）
            if ($artistId === 'new') {
                $userArtist = UserArtist::firstOrCreate(
                    ['external_user_id' => $userId, 'name' => $request->input('artist_name')],
                    []
                );
            } else {
                [$artistKind, $artistDbId] = $this->splitRef($artistId);
                abort_unless($artistKind === 'user', 403);
                $userArtist = UserArtist::findOrFail($artistDbId);
            }

            // ツアーとセットリストパターンを確定させる
            if ($tourId === 'new') {
                $tour = UserConcert::create([
                    'external_user_id' => $userId,
                    'user_artist_id' => $userArtist->id,
                    'title' => $request->input('tour_title'),
                    'type' => 1, // 単発ライブ（本人が個別に追加する時点ではツアーか単発かは判別できないため既定は単発）
                    'date1' => $request->input('tour_date1') ?: null,
                    'date2' => $request->input('tour_date2') ?: null,
                ]);
                $userSetlist = UserSetlist::create([
                    'user_concert_id' => $tour->id,
                    'external_user_id' => $userId,
                    'order_no' => 1,
                    'row' => 1,
                    'setlist' => [],
                    'encore' => [],
                ]);
            } else {
                [$tourKind, $tourDbId] = $this->splitRef($tourId);
                abort_unless($tourKind === 'user', 403);
                $tour = UserConcert::findOrFail($tourDbId);

                // 既存ツアーに曲目未入力の空パターンが既にあれば、重複作成せずそれを使う
                $emptyExisting = UserSetlist::where('user_concert_id', $tour->id)
                    ->get()
                    ->first(fn (UserSetlist $s) => empty($s->setlist) && empty($s->encore));

                if ($emptyExisting) {
                    $userSetlist = $emptyExisting;
                } else {
                    $nextOrderNo = (UserSetlist::where('user_concert_id', $tour->id)->max('order_no') ?? 0) + 1;
                    $userSetlist = UserSetlist::create([
                        'user_concert_id' => $tour->id,
                        'external_user_id' => $userId,
                        'order_no' => $nextOrderNo,
                        'row' => 1,
                        'setlist' => [],
                        'encore' => [],
                    ]);
                }
            }

            $toSongIds = function (array $titles) use ($userArtist) {
                $items = [];
                foreach ($titles as $title) {
                    $title = trim((string) $title);
                    if ($title === '') {
                        continue;
                    }
                    $song = UserSong::firstOrCreate(
                        ['user_artist_id' => $userArtist->id, 'title' => $title],
                        ['sort_order' => (UserSong::where('user_artist_id', $userArtist->id)->max('sort_order') ?? -1) + 1]
                    );
                    $items[] = ['song' => (string) $song->id];
                }
                return $items;
            };

            $userSetlist->update([
                'setlist' => $toSongIds($data['setlist'] ?? []),
                'encore' => $toSongIds($data['encore'] ?? []),
            ]);

            return Auth::guard('external')->user()->attendances()->create([
                'user_setlist_id' => $userSetlist->id,
                'attended_date' => $data['attended_date'],
                'venue' => $data['venue'] ?? null,
            ]);
        });

        return redirect()->route('mypage.attendances.show', $attendance)
            ->with('success', 'セットリストを追加しました。');
    }

    // ステップ4: 参加日・会場の入力フォーム（既存のセットリストパターンを選んだ場合に使用）。
    // setlistId は "official-{id}" / "user-{id}"。
    public function form($setlistId)
    {
        [$kind, $id] = $this->splitRef($setlistId);

        if ($kind === 'official') {
            $setlist = DbSetlist::with('tour.artist')->findOrFail($id);
        } else {
            $setlist = UserSetlist::with('concert.artist')->findOrFail($id);
        }

        $tour = $kind === 'official' ? $setlist->tour : $setlist->concert;

        // 単発開催（date2が無い）の場合は参加日が一意に決まるため、date1を初期値にする
        $defaultAttendedDate = !$tour->date2 ? $tour->date1 : null;

        // スケジュール表（db_concerts.schedule）から日付・会場の候補をドロップダウン用に用意する。
        // パースできる行のみ候補になり、パース失敗時は従来通り手入力にフォールバックできる。
        $scheduleOptions = $tour->parseScheduleEntries();

        return view('mypage.attendances.form', compact('setlistId', 'kind', 'setlist', 'tour', 'defaultAttendedDate', 'scheduleOptions'));
    }

    public function store(Request $request)
    {
        $userId = Auth::guard('external')->id();
        $setlistId = $request->input('setlist_id');
        [$kind, $id] = $this->splitRef($setlistId);

        if ($kind === 'official') {
            $setlist = DbSetlist::with('tour.artist')->find($id);
        } else {
            $setlist = UserSetlist::with('concert.artist')->find($id);
        }

        $validator = Validator::make($request->all(), [
            'attended_date' => [
                'required',
                'date',
                Rule::unique('external_user_attendances')->where(
                    fn ($query) => $query
                        ->where('external_user_id', $userId)
                        ->where($kind === 'official' ? 'db_setlist_id' : 'user_setlist_id', $id)
                ),
            ],
            'venue' => ['required', 'string', 'max:255'],
        ], [
            'attended_date.unique' => 'この公演はすでに登録されています。',
        ]);

        if (!$setlist) {
            abort(404);
        }

        $tour = $kind === 'official' ? $setlist->tour : $setlist->concert;

        if ($validator->fails()) {
            // セッションフラッシュ経由のリダイレクトだと、setlists()（一覧選択画面）の
            // 自動リダイレクト（パターンが1つしかない場合）が間に挟まってエラーが失われることがあるため、
            // リダイレクトせずこの場でform画面をエラー付きで直接描画する
            $defaultAttendedDate = !$tour->date2 ? $tour->date1 : null;
            $scheduleOptions = $tour->parseScheduleEntries();

            $errorBag = new \Illuminate\Support\ViewErrorBag();
            $errorBag->put('default', $validator->errors());

            return response()
                ->view('mypage.attendances.form', compact('setlistId', 'kind', 'setlist', 'tour', 'defaultAttendedDate', 'scheduleOptions') + [
                    'errors' => $errorBag,
                ])
                ->setStatusCode(422);
        }

        $data = $validator->validated();
        $data[$kind === 'official' ? 'db_setlist_id' : 'user_setlist_id'] = $id;

        $attendance = Auth::guard('external')->user()->attendances()->create($data);

        return redirect()->route('mypage.attendances.show', $attendance)
            ->with('success', 'セットリストを追加しました。');
    }

    // 参加記録の詳細（セットリスト表示）。Timeline経由で誰でも他人の投稿を閲覧できるが、
    // 前後ナビゲーション・編集操作は本人の記録内でのみ意味を持つため、本人閲覧時だけ表示する。
    public function show(ExternalUserAttendance $attendance)
    {
        $userId = Auth::guard('external')->id();
        $isOwner = $attendance->external_user_id === $userId;

        $attendance->load(['externalUser', 'dbSetlist.tour.artist', 'userSetlist.concert.artist', 'comments']);
        $isOfficial = (bool) $attendance->db_setlist_id;
        $tourSetlists = collect([$isOfficial ? $attendance->dbSetlist : $attendance->userSetlist]);
        $tour = $isOfficial ? $attendance->dbSetlist->tour : $attendance->userSetlist->concert;
        $artist = $tour->artist;
        $songs = $isOfficial ? DbSong::orderBy('id', 'asc')->get() : UserSong::where('user_artist_id', $artist->id)->orderBy('id', 'asc')->get();

        $previous = null;
        $next = null;
        if ($isOwner) {
            $previous = ExternalUserAttendance::where('external_user_id', $userId)
                ->where(function ($q) use ($attendance) {
                    $q->where('attended_date', '<', $attendance->attended_date)
                        ->orWhere(function ($q2) use ($attendance) {
                            $q2->where('attended_date', $attendance->attended_date)->where('id', '<', $attendance->id);
                        });
                })
                ->orderByDesc('attended_date')->orderByDesc('id')->first();
            $next = ExternalUserAttendance::where('external_user_id', $userId)
                ->where(function ($q) use ($attendance) {
                    $q->where('attended_date', '>', $attendance->attended_date)
                        ->orWhere(function ($q2) use ($attendance) {
                            $q2->where('attended_date', $attendance->attended_date)->where('id', '>', $attendance->id);
                        });
                })
                ->orderBy('attended_date')->orderBy('id')->first();
        }

        return view('mypage.attendances.show', compact('attendance', 'tourSetlists', 'tour', 'artist', 'isOfficial', 'songs', 'previous', 'next', 'isOwner'));
    }

    public function edit(ExternalUserAttendance $attendance)
    {
        $this->authorizeOwnership($attendance);

        $attendance->load(['dbSetlist.tour.artist', 'userSetlist.concert.artist']);

        return view('mypage.attendances.edit', compact('attendance'));
    }

    public function update(Request $request, ExternalUserAttendance $attendance)
    {
        $this->authorizeOwnership($attendance);

        $validator = Validator::make($request->all(), [
            'attended_date' => [
                'required',
                'date',
                Rule::unique('external_user_attendances')->where(
                    fn ($query) => $query
                        ->where('external_user_id', Auth::guard('external')->id())
                        ->where('db_setlist_id', $attendance->db_setlist_id)
                        ->where('user_setlist_id', $attendance->user_setlist_id)
                )->ignore($attendance->id),
            ],
            'venue' => ['required', 'string', 'max:255'],
        ], [
            'attended_date.unique' => 'この公演はすでに登録されています。',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $attendance->update($data);

        return redirect()->route('mypage.attendances.show', $attendance)->with('success', 'セットリストを更新しました。');
    }

    public function destroy(ExternalUserAttendance $attendance)
    {
        $this->authorizeOwnership($attendance);

        $attendance->delete();

        return redirect()->route('mypage.index')->with('success', 'セットリストを削除しました。');
    }

    private function authorizeOwnership(ExternalUserAttendance $attendance): void
    {
        abort_unless($attendance->external_user_id === Auth::guard('external')->id(), 403);
    }
}
