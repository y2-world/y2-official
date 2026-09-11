<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Models\ExternalUserAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            $query->whereHas('dbSetlist.tour', fn($q) => $q->where('artist_id', $artistId));
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
        $attendances = $query->paginate(20)->withQueryString();

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

    // ステップ2: そのアーティストのツアーを選ぶ
    public function tours($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $tours = DbConcert::where('artist_id', $artistId)
            ->orderBy('date1', 'desc')
            ->get();

        return view('mypage.attendances.tours', compact('artist', 'tours'));
    }

    // ステップ3: そのツアー内のセットリストパターンを選ぶ
    // パターンが1つしかない場合は選ぶまでもないので、そのまま入力フォームへ進める
    public function setlists($tourId)
    {
        $tour = DbConcert::findOrFail($tourId);
        $tourSetlists = DbSetlist::where('tour_id', $tourId)
            ->orderBy('row', 'asc')
            ->orderBy('order_no', 'asc')
            ->get();

        if ($tourSetlists->count() === 1) {
            return redirect()->route('mypage.attendances.form', $tourSetlists->first()->id);
        }

        $songs = DbSong::orderBy('id', 'asc')->get();

        return view('mypage.attendances.setlists', compact('tour', 'tourSetlists', 'songs'));
    }

    // ステップ4: 参加日・会場の入力フォーム
    public function form($dbSetlistId)
    {
        $dbSetlist = DbSetlist::with('tour.artist')->findOrFail($dbSetlistId);

        // 単発開催（date2が無い）の場合は参加日が一意に決まるため、date1を初期値にする
        $defaultAttendedDate = !$dbSetlist->tour->date2 ? $dbSetlist->tour->date1 : null;

        return view('mypage.attendances.form', compact('dbSetlist', 'defaultAttendedDate'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'db_setlist_id' => ['required', 'exists:db_setlists,id'],
            'attended_date' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
        ]);

        $attendance = Auth::guard('external')->user()->attendances()->create($data);

        return redirect()->route('mypage.attendances.show', $attendance)
            ->with('success', 'ライブの参加記録を追加しました。');
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

        $data = $request->validate([
            'attended_date' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
        ]);

        $attendance->update($data);

        return redirect()->route('mypage.attendances.show', $attendance)->with('success', '参加記録を更新しました。');
    }

    public function destroy(ExternalUserAttendance $attendance)
    {
        $this->authorizeOwnership($attendance);

        $attendance->delete();

        return redirect()->route('mypage.index')->with('success', '参加記録を削除しました。');
    }

    private function authorizeOwnership(ExternalUserAttendance $attendance): void
    {
        abort_unless($attendance->external_user_id === Auth::guard('external')->id(), 403);
    }
}
