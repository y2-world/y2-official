<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Models\ExternalUserAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Auth::guard('external')->user()
            ->attendances()
            ->with('dbSetlist.tour.artist');

        $artistId = $request->input('artist_id');
        $filterArtist = null;
        if ($artistId) {
            // type=4（ソロ）は本人単独のプロジェクトであり、アーティスト本体のライブ履歴には含めない
            $query->whereHas('dbSetlist.tour', fn($q) => $q->where('artist_id', $artistId)->where('type', '!=', 4));
            $filterArtist = Artist::find($artistId);
        }

        $year = $request->input('year');
        if ($year) {
            $query->whereYear('attended_date', $year);
        }

        $songId = $request->input('song_id');
        $song = null;
        $songNumber = null;
        $previousSong = null;
        $nextSong = null;
        if ($songId) {
            $song = DbSong::find($songId);
            if ($song) {
                // 自分の参加記録を古い順に見ていったとき、そのアーティストの曲の中で
                // 何番目に初めて自分のリストに登場したかを#として表示する
                $orderedAttendances = Auth::guard('external')->user()
                    ->attendances()
                    ->whereHas('dbSetlist.tour', fn($q) => $q->where('artist_id', $song->artist_id))
                    ->with('dbSetlist')
                    ->orderBy('attended_date')
                    ->get();

                $firstSeenOrder = [];
                foreach ($orderedAttendances as $attendance) {
                    $setlist = $attendance->dbSetlist;
                    if (!$setlist) {
                        continue;
                    }
                    foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                        if (isset($s['song']) && is_numeric($s['song'])) {
                            $sid = (int)$s['song'];
                            if (!isset($firstSeenOrder[$sid])) {
                                $firstSeenOrder[$sid] = count($firstSeenOrder) + 1;
                            }
                        }
                    }
                }

                $songNumber = $firstSeenOrder[(int)$songId] ?? null;

                // 初めて聴いた順で前後の曲を求める
                $orderedSongIds = array_keys($firstSeenOrder);
                $currentIndex = array_search((int)$songId, $orderedSongIds, true);
                $previousSongId = $currentIndex !== false && $currentIndex > 0 ? $orderedSongIds[$currentIndex - 1] : null;
                $nextSongId = $currentIndex !== false && $currentIndex < count($orderedSongIds) - 1 ? $orderedSongIds[$currentIndex + 1] : null;
                $previousSong = $previousSongId ? DbSong::find($previousSongId) : null;
                $nextSong = $nextSongId ? DbSong::find($nextSongId) : null;
            }
            $matchingSetlistIds = DbSetlist::all()->filter(function (DbSetlist $setlist) use ($songId) {
                foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                    if (isset($s['song']) && (int)$s['song'] === (int)$songId) {
                        return true;
                    }
                }
                return false;
            })->pluck('id');
            $query->whereIn('db_setlist_id', $matchingSetlistIds);
        }

        if ($song) {
            $query->orderByDesc('attended_date');
        } else {
            $query->orderBy('attended_date');
        }
        $attendances = $query->get();

        $artists = Artist::whereHas('tours', function ($q) {
            $q->whereHas('tourSetlists', function ($q2) {
                $q2->whereHas('attendances', function ($q3) {
                    $q3->where('external_user_id', Auth::guard('external')->id());
                });
            });
        })->orderBy('name')->get();

        $years = Auth::guard('external')->user()
            ->attendances()
            ->whereNotNull('attended_date')
            ->get()
            ->pluck('attended_date')
            ->map(fn ($date) => $date->format('Y'))
            ->unique()
            ->sort()
            ->values();

        return view('mypage.attendances.index', compact('attendances', 'artists', 'artistId', 'song', 'songNumber', 'filterArtist', 'years', 'year', 'previousSong', 'nextSong'));
    }

    // ステップ1: アーティストを選ぶ
    public function create()
    {
        $artists = Artist::whereHas('tours')->orderBy('name')->get();

        return view('mypage.attendances.create', compact('artists'));
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
    // artistId=new の場合は、まだDBに存在しない新規アーティスト（$nameがその名前）を表す。
    public function tours(Request $request, $artistId)
    {
        if ($artistId === 'new') {
            $artist = null;
            $artistName = $request->query('name', '');
            abort_if($artistName === '', 404);
            $tours = collect();
        } else {
            $artist = Artist::findOrFail($artistId);
            $artistName = $artist->name;
            $tours = DbConcert::where('artist_id', $artistId)
                ->orderBy('date1', 'desc')
                ->get();
        }

        return view('mypage.attendances.tours', compact('artist', 'artistId', 'artistName', 'tours'));
    }

    // 一覧に無いツアー・ライブ名を入力し、セットリスト入力画面へ進む。
    // この時点でもDBに何も保存しない。アーティスト名・ツアー名はクエリparamで次画面に引き継ぐ。
    public function newTour(Request $request, $artistId)
    {
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
        $tour = DbConcert::findOrFail($tourId);
        $tourSetlists = DbSetlist::where('tour_id', $tourId)
            ->orderBy('row', 'asc')
            ->orderBy('order_no', 'asc')
            ->get();

        if ($tourSetlists->count() === 1) {
            $only = $tourSetlists->first();
            if (empty($only->setlist) && empty($only->encore)) {
                return redirect()->route('mypage.attendances.setlist_create', ['artistId' => $tour->artist_id, 'tourId' => $tour->id]);
            }
            return redirect()->route('mypage.attendances.form', $only->id);
        }

        $songs = DbSong::orderBy('id', 'asc')->get();

        return view('mypage.attendances.setlists', compact('tour', 'tourSetlists', 'songs'));
    }

    // 既存ツアーに、別日程用の新しいセットリストパターンを追加する画面へ進む（未保存）
    public function newSetlistPattern($tourId)
    {
        $tour = DbConcert::findOrFail($tourId);

        return redirect()->route('mypage.attendances.setlist_create', ['artistId' => $tour->artist_id, 'tourId' => $tour->id]);
    }

    // 新規セットリストパターンの曲目入力画面。
    // artistId/tourId が 'new' の場合や dbSetlistId が無い場合は、まだ何もDBに存在しない状態。
    public function setlistCreate(Request $request, $artistId, $tourId)
    {
        $artistName = null;
        $tourTitle = null;
        $existingArtistId = null;
        $tourDate1 = null;
        $tourDate2 = null;

        if ($artistId !== 'new') {
            $artist = Artist::findOrFail($artistId);
            $artistName = $artist->name;
            $existingArtistId = $artist->id;
        } else {
            $artistName = $request->query('name', '');
            abort_if($artistName === '', 404);
        }

        if ($tourId !== 'new') {
            $tour = DbConcert::findOrFail($tourId);
            $tourTitle = $tour->title;
        } else {
            $tourTitle = $request->query('title', '');
            abort_if($tourTitle === '', 404);
            $tourDate1 = $request->query('date1');
            $tourDate2 = $request->query('date2');
        }

        $songOptions = $existingArtistId
            ? DbSong::where('artist_id', $existingArtistId)->orderBy('id', 'asc')->pluck('title')
            : collect();

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

        $artistName = $artistId === 'new' ? $request->input('artist_name') : Artist::findOrFail($artistId)->name;
        $tourTitle = $tourId === 'new' ? $request->input('tour_title') : DbConcert::findOrFail($tourId)->title;
        $tourDate1 = $tourId === 'new' ? $request->input('tour_date1') : null;
        $tourDate2 = $tourId === 'new' ? $request->input('tour_date2') : null;

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
    public function setlistStore(Request $request, $artistId, $tourId)
    {
        $validator = Validator::make($request->all(), [
            'setlist' => ['array'],
            'setlist.*' => ['nullable', 'string', 'max:255'],
            'encore' => ['array'],
            'encore.*' => ['nullable', 'string', 'max:255'],
            'attended_date' => ['required', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $attendance = DB::transaction(function () use ($request, $artistId, $tourId, $data) {
            if ($artistId === 'new') {
                $artist = Artist::firstOrCreate(
                    ['name' => $request->input('artist_name')],
                    ['visible' => 1]
                );
            } else {
                $artist = Artist::findOrFail($artistId);
            }

            if ($tourId === 'new') {
                $tour = DbConcert::create([
                    'artist_id' => $artist->id,
                    'title' => $request->input('tour_title'),
                    'type' => 1, // 単発ライブ（本人が個別に追加する時点ではツアーか単発かは判別できないため既定は単発。後から管理画面で編集可能）
                    'date1' => $request->input('tour_date1') ?: null,
                    'date2' => $request->input('tour_date2') ?: null,
                ]);
                $dbSetlist = DbSetlist::create([
                    'tour_id' => $tour->id,
                    'order_no' => 1,
                    'row' => 1,
                    'setlist' => [],
                    'encore' => [],
                ]);
            } else {
                $tour = DbConcert::findOrFail($tourId);
                // 既存ツアーに曲目未入力の空パターンが既にあれば、重複作成せずそれを使う
                $emptyExisting = DbSetlist::where('tour_id', $tour->id)
                    ->get()
                    ->first(fn (DbSetlist $s) => empty($s->setlist) && empty($s->encore));

                if ($emptyExisting) {
                    $dbSetlist = $emptyExisting;
                } else {
                    $nextOrderNo = (DbSetlist::where('tour_id', $tour->id)->max('order_no') ?? 0) + 1;
                    $dbSetlist = DbSetlist::create([
                        'tour_id' => $tour->id,
                        'order_no' => $nextOrderNo,
                        'row' => 1,
                        'setlist' => [],
                        'encore' => [],
                    ]);
                }
            }

            $toSongIds = function (array $titles) use ($artist) {
                $items = [];
                foreach ($titles as $title) {
                    $title = trim((string) $title);
                    if ($title === '') {
                        continue;
                    }
                    $song = DbSong::firstOrCreate(
                        ['artist_id' => $artist->id, 'title' => $title],
                        []
                    );
                    $items[] = ['song' => (string) $song->id];
                }
                return $items;
            };

            $dbSetlist->update([
                'setlist' => $toSongIds($data['setlist'] ?? []),
                'encore' => $toSongIds($data['encore'] ?? []),
            ]);

            return Auth::guard('external')->user()->attendances()->create([
                'db_setlist_id' => $dbSetlist->id,
                'attended_date' => $data['attended_date'],
                'venue' => $data['venue'] ?? null,
            ]);
        });

        return redirect()->route('mypage.attendances.show', $attendance)
            ->with('success', 'セットリストを追加しました。');
    }

    // ステップ4: 参加日・会場の入力フォーム（既存セットリストパターンを選んだ場合のみ使用）
    public function form($dbSetlistId)
    {
        $dbSetlist = DbSetlist::with('tour.artist')->findOrFail($dbSetlistId);

        // 単発開催（date2が無い）の場合は参加日が一意に決まるため、date1を初期値にする
        $defaultAttendedDate = !$dbSetlist->tour->date2 ? $dbSetlist->tour->date1 : null;

        // スケジュール表（db_concerts.schedule）から日付・会場の候補をドロップダウン用に用意する。
        // パースできる行のみ候補になり、パース失敗時は従来通り手入力にフォールバックできる。
        $scheduleOptions = $dbSetlist->tour->parseScheduleEntries();

        return view('mypage.attendances.form', compact('dbSetlist', 'defaultAttendedDate', 'scheduleOptions'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_setlist_id' => ['required', 'exists:db_setlists,id'],
            'attended_date' => [
                'required',
                'date',
                Rule::unique('external_user_attendances')->where(
                    fn ($query) => $query
                        ->where('external_user_id', Auth::guard('external')->id())
                        ->where('db_setlist_id', $request->input('db_setlist_id'))
                ),
            ],
            'venue' => ['nullable', 'string', 'max:255'],
        ], [
            'attended_date.unique' => 'この公演はすでに登録されています。',
        ]);

        if ($validator->fails()) {
            // セッションフラッシュ経由のリダイレクトだと、setlists()（一覧選択画面）の
            // 自動リダイレクト（パターンが1つしかない場合）が間に挟まってエラーが失われることがあるため、
            // リダイレクトせずこの場でform画面をエラー付きで直接描画する
            $dbSetlist = DbSetlist::with('tour.artist')->findOrFail($request->input('db_setlist_id'));
            $defaultAttendedDate = !$dbSetlist->tour->date2 ? $dbSetlist->tour->date1 : null;
            $scheduleOptions = $dbSetlist->tour->parseScheduleEntries();

            $errorBag = new \Illuminate\Support\ViewErrorBag();
            $errorBag->put('default', $validator->errors());

            return response()
                ->view('mypage.attendances.form', compact('dbSetlist', 'defaultAttendedDate', 'scheduleOptions') + [
                    'errors' => $errorBag,
                ])
                ->setStatusCode(422);
        }

        $data = $validator->validated();

        $attendance = Auth::guard('external')->user()->attendances()->create($data);

        return redirect()->route('mypage.attendances.show', $attendance)
            ->with('success', 'セットリストを追加しました。');
    }

    public function show(ExternalUserAttendance $attendance)
    {
        $this->authorizeOwnership($attendance);

        $attendance->load('dbSetlist.tour.artist');
        $tourSetlists = collect([$attendance->dbSetlist]);
        $songs = DbSong::orderBy('id', 'asc')->get();

        $userId = Auth::guard('external')->id();
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

        return view('mypage.attendances.show', compact('attendance', 'tourSetlists', 'songs', 'previous', 'next'));
    }

    public function edit(ExternalUserAttendance $attendance)
    {
        $this->authorizeOwnership($attendance);

        $attendance->load('dbSetlist.tour.artist');

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
                )->ignore($attendance->id),
            ],
            'venue' => ['nullable', 'string', 'max:255'],
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
