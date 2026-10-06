@extends('layouts.app')
@php
    // user_id指定時は他ユーザーの一覧を見ている。全リンクにuser_idを引き継がないと
    // 「アーティストで絞り込んだ瞬間、閲覧者自身のデータに切り替わる」バグになる。
    $isSelf = $targetUser->id === \Illuminate\Support\Facades\Auth::guard('external')->id();
    $userIdParam = $isSelf ? [] : ['user_id' => $targetUser->id];

    // ブラウザのタブのタイトルは、本家（曲・アーティスト・年・会場のページ）と同じく絞り込みに合わせる
    $pageTitle = $song ? ($initialTitle ?? $song->title)
        : ($filterArtist ? $filterArtist->name
        : ($year ? $year
        : ($venue ? 'Venue : ' . $venue
        : ($isSelf ? 'My Setlists' : ($targetUser->name ?: 'ゲスト') . "'s Setlists"))));
@endphp

@section('title', 'Yuki Official - ' . $pageTitle)

@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @if ($song)
                {{-- 曲単位の絞り込み：sl_songs/show.blade.php と全く同じシンプルな構造 --}}
                @include('database._breadcrumb', ['breadcrumbs' => [
                    ['label' => 'My Page', 'url' => route('mypage.index')],
                    ['label' => $song->title],
                ]])
                {{-- #（曲番）は選んでいるタブによって意味が変わる：Live Performances中はアーティスト内の
                     sort_order順位（常に存在）、My Live Attendances中は自分の参加記録での初登場順
                     （自分がまだ聴いた記録がなければ非表示）。両方をあらかじめ用意し、タブ切り替えJSで出し分ける。 --}}
                <p class="database-subtitle song-number-performances" style="{{ $secondTab === 'mine' ? 'display: none;' : '' }}">#{{ $songNumberPerformances }}</p>
                @if ($songNumberMine)
                    <p class="database-subtitle song-number-mine" style="{{ $secondTab === 'mine' ? '' : 'display: none;' }}">#{{ $songNumberMine }}</p>
                @endif
                <h1 class="database-title" style="">{{ $initialTitle ?? $song->title }}</h1>

                <div style="font-size: 1rem; color: rgba(255, 255, 255, 0.9); line-height: 1.8;">
                    @if ($song->artist)
                        <div style="">
                            <a href="{{ route('mypage.attendances.index', $userIdParam + ['artist_id' => $songKind . '-' . $song->artist->id]) }}"
                                style="color: white; text-decoration: underline;">
                                {{ $song->artist->name }}
                            </a>
                        </div>
                    @endif
                </div>

                {{-- 入っているシングル・アルバム（曲のページと同じ）。公式の曲は Database、マイページで作った曲はマイページのページへ --}}
                @php
                    $songSingle = $song->singleFromTracklist;
                    $songAlbum = $song->albumFromTracklist;
                    $isOfficialSong = $songKind === 'official';
                    $songAlbumTitle = $isOfficialSong && $songAlbum ? $song->albumDisplayTitleFromTracklist : $songAlbum?->title;
                @endphp
                @if ($songSingle)
                    <p class="database-subtitle" style="font-weight: 400;"><strong>Single:</strong> <a href="{{ $isOfficialSong ? route('singles.show', $songSingle->id) : route('mypage.user_singles.show', $songSingle->id) }}" style="color: white; text-decoration: underline;">{{ $songSingle->title }}</a></p>
                    @if ($songSingle->date)
                        <p class="database-subtitle">Release: {{ \Carbon\Carbon::parse($songSingle->date)->format('Y.m.d') }}</p>
                    @endif
                @endif
                @if ($songAlbum)
                    <p class="database-subtitle" style="font-weight: 400;"><strong>Album:</strong> <a href="{{ $isOfficialSong ? route('albums.show', $songAlbum->id) : route('mypage.user_albums.show', $songAlbum->id) }}" style="color: white; text-decoration: underline;">{{ $songAlbumTitle }}</a></p>
                    @if ($songAlbum->date)
                        <p class="database-subtitle">Release: {{ \Carbon\Carbon::parse($songAlbum->date)->format('Y.m.d') }}</p>
                    @endif
                @elseif ($isOfficialSong || \App\Models\UserAlbum::where('user_artist_id', $song->user_artist_id)->exists())
                    <p class="database-subtitle">アルバム未収録</p>
                @endif

                {{-- 虫眼鏡アイコン（SP表示のみ、見出しブロックの右下）：押すとフォームが開き、ボタン自体は隠れる --}}
                <button type="button" id="spSearchButtonMyPageSong" class="sp" onclick="document.getElementById('spSearchButtonMyPageSong').style.display='none'; document.getElementById('spSearchFormMyPageSong').style.display='block';" style="position: absolute; bottom: 8px; right: 8px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                </button>

                {{-- 検索フォーム（SP表示） --}}
                <div class="sp" id="spSearchFormMyPageSong" style="margin-top: 10px; display: none;">
                    <div>
                        @livewire('my-page-song-search')
                        {{-- 閉じるボタン --}}
                        <div style="text-align: center; margin-top: 15px;">
                            <button type="button" onclick="document.getElementById('spSearchFormMyPageSong').style.display='none'; document.getElementById('spSearchButtonMyPageSong').style.display='inline-flex';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 検索フォーム（PC表示のみ） --}}
                <div class="database-search pc song-search-top-right">
                    <div>
                        @livewire('my-page-song-search')
                    </div>
                </div>
            @else
                <div class="setlists-header-row">
                    <div style="flex-shrink: 0;">
                        @php
                            $viewerLabel = $isSelf ? 'すべてのセットリスト' : ($targetUser->name ?: 'ゲスト') . 'の参加記録';
                            $viewerLabelWithYear = $viewerLabel;
                        @endphp
                        @if ($filterArtist)
                            @php
                                // アーティスト名からアーティストのページへ（公式は Database にライブか曲があるときだけ、ユーザー登録はそのアーティストのページ）
                                $filterArtistLink = str_starts_with((string) $artistId, 'official-')
                                    ? ($filterArtist->visible && (\App\Models\DbConcert::where('artist_id', $filterArtist->id)->exists() || \App\Models\DbSong::where('artist_id', $filterArtist->id)->exists()) ? route('database.artist', $filterArtist->id) : null)
                                    : route('mypage.user_artists.show', $filterArtist->id);
                            @endphp
                            <h1 class="database-title" style="white-space: nowrap;">@if ($filterArtistLink)<a href="{{ $filterArtistLink }}" style="color: inherit; text-decoration: none;">{{ $filterArtist->name }}</a>@else{{ $filterArtist->name }}@endif</h1>
                            <p class="database-subtitle" style="margin: 4px 0 0;">{{ $viewerLabel }}</p>
                        @elseif ($year)
                            <h1 class="database-title" style="white-space: nowrap;">{{ $year }}</h1>
                            <p class="database-subtitle" style="margin: 4px 0 0;">{{ $viewerLabelWithYear }}</p>
                        @elseif ($venue)
                            <h1 class="database-title" style="white-space: nowrap;">{{ $venue }}</h1>
                            <p class="database-subtitle" style="margin: 4px 0 0;">{{ $viewerLabel }}</p>
                        @else
                            {{-- 絞り込みなし：本家の Setlists と同じ作りの「My Setlists」。本人には参加記録の追加ボタンを出す --}}
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <h1 class="database-title" style="white-space: nowrap;">{{ $isSelf ? 'My Setlists' : ($targetUser->name ?: 'ゲスト') . "'s Setlists" }}</h1>
                                @if ($isSelf)
                                    <a href="{{ route('mypage.attendances.create') }}" class="mypage-add-button" title="セットリストを追加" style="background: white; color: #764ba2; flex: 0 0 auto;">
                                        <i class="fa-solid fa-plus"></i>
                                    </a>
                                @endif
                            </div>
                            <p class="database-subtitle" style="margin: 4px 0 0;">すべてのセットリスト</p>
                        @endif
                    </div>
                    <div class="header-selects" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; max-width: 100%;">
                        {{-- 虫眼鏡アイコン（SP表示のみ） --}}
                        <button type="button" id="spSearchButtonMyAttendances" class="sp" onclick="var form = document.getElementById('spSearchFormMyAttendances'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                        </button>
                        <select class="year-select" name="select" onchange="if (this.value) window.location.href=this.value; else window.location.href='{{ route('mypage.attendances.index', $userIdParam + array_filter(['year' => $year])) }}';">
                            <option value="" {{ $artistId ? '' : 'selected' }}>All Artists</option>
                            @foreach ($officialArtists as $artist)
                                @php $ref = 'official-' . $artist->id; @endphp
                                <option value="{{ route('mypage.attendances.index', $userIdParam + array_filter(['artist_id' => $ref, 'year' => $year])) }}" {{ $artistId === $ref ? 'selected' : '' }}>{{ $artist->name }}</option>
                            @endforeach
                            @foreach ($myArtists as $artist)
                                @php $ref = 'user-' . $artist->id; @endphp
                                <option value="{{ route('mypage.attendances.index', $userIdParam + array_filter(['artist_id' => $ref, 'year' => $year])) }}" {{ $artistId === $ref ? 'selected' : '' }}>{{ $artist->name }}</option>
                            @endforeach
                        </select>
                        <select class="year-select" name="select" onchange="if (this.value) window.location.href=this.value; else window.location.href='{{ route('mypage.attendances.index', $userIdParam + array_filter(['artist_id' => $artistId])) }}';">
                            <option value="" {{ $year ? '' : 'selected' }}>All Years</option>
                            @foreach ($years as $y)
                                <option value="{{ route('mypage.attendances.index', $userIdParam + array_filter(['artist_id' => $artistId, 'year' => $y])) }}" {{ (string)$year === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0; display: none;">
                        @livewire('my-page-song-search', ['artistId' => str_starts_with((string) $artistId, 'official-') ? (int) substr($artistId, 9) : null])
                    </div>
                </div>

                {{-- 検索フォーム（SP表示） --}}
                <div class="sp" id="spSearchFormMyAttendances" style="margin-top: 15px; display: none;">
                    @livewire('my-page-song-search', ['artistId' => str_starts_with((string) $artistId, 'official-') ? (int) substr($artistId, 9) : null])
                </div>
            @endif
        </div>
    </div>

    <div class="container database-year-content">
        @if ($song && $secondTab)
            {{-- 曲単位の絞り込み：「Live Performances」（この曲が演奏された全ライブ）と
                 「My Live Attendances」（自分の参加記録）をタブで切り替える。
                 どちらを最初に見せるかはコントローラ側で遷移元によって決めている。 --}}
            <style>
                @media (max-width: 768px) {
                    .song-performance-tabs {
                        justify-content: center;
                    }
                }
            </style>
            <div class="row justify-content-center">
            <div class="col-xl-9">
            <div class="song-performance-tabs" style="display: flex; gap: 8px; margin-bottom: 15px;">
                <button type="button" class="song-performance-tab-btn @if($secondTab === 'performances') is-active @endif" data-tab-target="live-performances-panel"
                    style="padding: 8px 16px; border: none; border-radius: 20px; font-weight: 500; cursor: pointer; {{ $secondTab === 'performances' ? 'background: #667eea; color: white;' : 'background: white; color: #667eea; border: 1px solid #667eea;' }}">
                    Live Performances
                </button>
                <button type="button" class="song-performance-tab-btn @if($secondTab === 'mine') is-active @endif" data-tab-target="second-tab-panel"
                    style="padding: 8px 16px; border-radius: 20px; font-weight: 500; cursor: pointer; {{ $secondTab === 'mine' ? 'background: #667eea; color: white; border: none;' : 'background: white; color: #667eea; border: 1px solid #667eea;' }}">
                    My Live Attendances
                </button>
            </div>

            <div id="live-performances-panel" style="{{ $secondTab === 'performances' ? 'display: block;' : 'display: none;' }}">
                @if ($tours->isEmpty())
                    <p style="color: #718096; text-align: center;">演奏記録がありません。</p>
                @else
                    <table class="table table-striped count">
                        <thead>
                            <tr>
                                <th class="mobile">#</th>
                                <th class="mobile">開催日</th>
                                <th class="mobile">タイトル</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tours as $tour)
                                <tr>
                                    <td></td>
                                    @if (isset($tour->date1) && isset($tour->date2))
                                        <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                                            {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                                    @elseif(isset($tour->date1) && !isset($tour->date2))
                                        <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                                    @else
                                        <td class="td_date"></td>
                                    @endif
                                    <td class="td_title">
                                        @if ($songKind === 'official')
                                            <a href="{{ route('live.show', $tour->id) }}">{{ $tour->title }}</a>
                                        @else
                                            <a href="{{ route('mypage.user_concerts.show', $tour->id) }}">{{ $tour->title }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div id="second-tab-panel" style="{{ $secondTab === 'mine' ? 'display: block;' : 'display: none;' }}">
                @if ($attendances->isEmpty())
                    <p style="color: #718096; text-align: center;">参加記録がありません。</p>
                @else
                    <table class="table table-striped count">
                        <thead>
                            <tr>
                                <th class="mobile">#</th>
                                <th class="mobile">開催日</th>
                                <th class="mobile">タイトル</th>
                                <th class="pc">会場</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attendances as $attendance)
                                <tr>
                                    <td></td>
                                    <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                    <td><a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'attendances']) }}">{{ $attendance->attendedTour->title ?? '-' }}</a></td>
                                    <td class="pc">{{ $attendance->venue }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            </div>
            </div>
        @endif

        @if (!$song || !$secondTab)
        @if ($attendances->isEmpty())
            <p style="text-align: center; color: #999; margin-top: 40px;">まだ参加したライブが記録されていません。</p>
        @endif
        @if (!$attendances->isEmpty())
            @if ($artistId || $song)
                {{-- アーティスト単位（または曲単位）の絞り込み：artists.show と同じ4列構成 --}}
                <table class="table table-striped count">
                    <thead>
                        <tr>
                            <th class="mobile">#</th>
                            <th class="mobile">開催日</th>
                            <th class="mobile">タイトル</th>
                            <th class="pc">会場</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $attendance)
                            <tr>
                                <td></td>
                                <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                <td><a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'attendances']) }}">{{ $attendance->attendedTour->title ?? '-' }}</a></td>
                                <td class="pc">{{ $attendance->venue }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif ($year || $venue)
                {{-- 年単位・会場単位の絞り込み：years.show と同じ構成（table.count、pc:アーティストが先） --}}
                <table class="table table-striped count">
                    <thead>
                        <tr>
                            <th class="mobile">#</th>
                            <th class="mobile">開催日</th>
                            <th class="pc">アーティスト</th>
                            <th class="sp">アーティスト / タイトル</th>
                            <th class="pc">タイトル</th>
                            <th class="pc">会場</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $attendance)
                            @php
                                $isOfficial = $attendance->is_official;
                                $tour = $attendance->attendedTour;
                                $isFes = in_array((int) ($tour?->type ?? 0), [2, 3, 4], true);
                                $artistRef = $tour?->artist ? ($isOfficial ? 'official' : 'user') . '-' . $tour->artist->id : null;
                            @endphp
                            <tr>
                                <td></td>
                                <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                @if ($tour?->artist && !$isFes)
                                    <td class="pc">
                                        <a href="{{ route('mypage.attendances.index', $userIdParam + ['artist_id' => $artistRef]) }}">{{ $tour->artist->name }}</a>
                                    </td>
                                    <td class="sp">
                                        <a href="{{ route('mypage.attendances.index', $userIdParam + ['artist_id' => $artistRef]) }}">{{ $tour->artist->name }}</a>
                                        /
                                        <a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'attendances']) }}">{{ $tour->title ?? '-' }}</a>
                                    </td>
                                @else
                                    <td class="pc"></td>
                                    <td class="sp"><a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'attendances']) }}">{{ $tour->title ?? '-' }}</a></td>
                                @endif
                                <td class="pc"><a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'attendances']) }}">{{ $tour->title ?? '-' }}</a></td>
                                <td class="pc">{{ $attendance->venue }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                {{-- 全件表示：sl_setlists/index.blade.php（Setlistsトップ）と同じく、これからのライブと今までのライブに分け、
                     今までのライブは新しい順（# は大きい数から）。会場は会場で絞り込んだ一覧へ --}}
                @php
                    $today = now()->toDateString();
                    $isUpcoming = fn ($a) => $a->attended_date && $a->attended_date->toDateString() > $today;
                    $upcomingAttendances = $attendances->filter($isUpcoming)->values();
                    $pastAttendances = $attendances->reject($isUpcoming)->reverse()->values();
                    $attendanceGroups = array_filter([
                        $upcomingAttendances->isNotEmpty() ? ['label' => 'Upcoming Shows', 'rows' => $upcomingAttendances, 'countDown' => false] : null,
                        ['label' => 'Past Shows', 'rows' => $pastAttendances, 'countDown' => true],
                    ]);
                @endphp
                @foreach ($attendanceGroups as $group)
                    <h3 style="margin-top: {{ $loop->first ? '0' : '30px' }}; margin-bottom: 15px;">{{ $group['label'] }}</h3>
                    <table class="table table-striped fixed-cols">
                        <thead>
                            <tr>
                                <th class="mobile col-no">#</th>
                                <th class="mobile col-date">開催日</th>
                                <th class="sp">アーティスト / タイトル</th>
                                <th class="pc col-artist">アーティスト</th>
                                <th class="pc">タイトル</th>
                                <th class="pc col-venue">会場</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['rows'] as $index => $attendance)
                                @php
                                    $isOfficial = $attendance->is_official;
                                    $tour = $attendance->attendedTour;
                                    $isFes = in_array((int) ($tour?->type ?? 0), [2, 3, 4], true);
                                    $artistRef = $tour?->artist ? ($isOfficial ? 'official' : 'user') . '-' . $tour->artist->id : null;
                                    $showUrl = route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'attendances']);
                                @endphp
                                <tr>
                                    <td>{{ $group['countDown'] ? $group['rows']->count() - $index : $index + 1 }}</td>
                                    <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                    @if ($tour?->artist && !$isFes)
                                        <td class="sp">
                                            <a href="{{ route('mypage.attendances.index', $userIdParam + ['artist_id' => $artistRef]) }}">{{ $tour->artist->name }}</a>
                                            /
                                            <a href="{{ $showUrl }}">{{ $tour->title ?? '-' }}</a>
                                        </td>
                                        <td class="pc td_artist">
                                            <a href="{{ route('mypage.attendances.index', $userIdParam + ['artist_id' => $artistRef]) }}">{{ $tour->artist->name }}</a>
                                        </td>
                                    @else
                                        <td class="sp">
                                            <a href="{{ $showUrl }}">{{ $tour->title ?? '-' }}</a>
                                        </td>
                                        <td class="pc"></td>
                                    @endif
                                    <td class="pc">
                                        <a href="{{ $showUrl }}">{{ $tour->title ?? '-' }}</a>
                                    </td>
                                    <td class="pc">
                                        @if ($attendance->venue)
                                            <a href="{{ route('mypage.attendances.index', $userIdParam + ['venue' => $attendance->venue]) }}">{{ $attendance->venue }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            @endif
        @endif
        @endif

        @if ($song)
            {{-- 前後リンクも#と同じくタブに応じて意味が変わる（Live Performances: sort_order順、
                 My Live Attendances: 初めて聴いた順）。両パターンを用意しJSで出し分ける。 --}}
            <div class="song-nav-performances" style="display: {{ $secondTab === 'mine' ? 'none' : 'flex' }}; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                @if ($previousSongPerformances)
                    <a href="{{ route('mypage.attendances.index', $userIdParam + ['song_id' => $songKind . '-' . $previousSongPerformances->id, 'tab' => 'performances']) }}" rel="prev"
                       style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                        <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                        Previous
                    </a>
                @else
                    <div></div>
                @endif
                @if ($nextSongPerformances)
                    <a href="{{ route('mypage.attendances.index', $userIdParam + ['song_id' => $songKind . '-' . $nextSongPerformances->id, 'tab' => 'performances']) }}" rel="next"
                       style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                        Next
                        <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                    </a>
                @endif
            </div>
            <div class="song-nav-mine" style="display: {{ $secondTab === 'mine' ? 'flex' : 'none' }}; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                @if ($previousSongMine)
                    <a href="{{ route('mypage.attendances.index', $userIdParam + ['song_id' => $songKind . '-' . $previousSongMine->id, 'tab' => 'mine']) }}" rel="prev"
                       style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                        <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                        Previous
                    </a>
                @else
                    <div></div>
                @endif
                @if ($nextSongMine)
                    <a href="{{ route('mypage.attendances.index', $userIdParam + ['song_id' => $songKind . '-' . $nextSongMine->id, 'tab' => 'mine']) }}" rel="next"
                       style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                        Next
                        <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                    </a>
                @endif
            </div>
        @endif
    </div>
@endsection

@section('page-script')
@if ($song && $secondTab)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var buttons = document.querySelectorAll('.song-performance-tab-btn');
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    buttons.forEach(function (b) {
                        b.classList.remove('is-active');
                        b.style.background = 'white';
                        b.style.color = '#667eea';
                        b.style.border = '1px solid #667eea';
                    });
                    btn.classList.add('is-active');
                    btn.style.background = '#667eea';
                    btn.style.color = 'white';
                    btn.style.border = 'none';

                    document.getElementById('live-performances-panel').style.display = 'none';
                    document.getElementById('second-tab-panel').style.display = 'none';
                    document.getElementById(btn.dataset.tabTarget).style.display = 'block';

                    // #（曲番）とPrevious/Nextも選んだタブに応じて意味が変わるため、
                    // 同じタイミングで表示を切り替える
                    var isMine = btn.dataset.tabTarget === 'second-tab-panel';
                    var songNumberPerformances = document.querySelector('.song-number-performances');
                    var songNumberMine = document.querySelector('.song-number-mine');
                    if (songNumberPerformances) songNumberPerformances.style.display = isMine ? 'none' : '';
                    if (songNumberMine) songNumberMine.style.display = isMine ? '' : 'none';

                    var navPerformances = document.querySelector('.song-nav-performances');
                    var navMine = document.querySelector('.song-nav-mine');
                    if (navPerformances) navPerformances.style.display = isMine ? 'none' : 'flex';
                    if (navMine) navMine.style.display = isMine ? 'flex' : 'none';

                    // Previous/Nextで移動した先でも選んだタブをキープできるよう、URLに反映する
                    var url = new URL(window.location.href);
                    url.searchParams.set('tab', isMine ? 'mine' : 'performances');
                    history.replaceState(null, '', url);
                });
            });
        });
    </script>
@endif
@endsection
