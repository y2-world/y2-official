@extends('layouts.app')
@section('title', 'Yuki Official - セットリスト一覧')

@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @if ($song)
                {{-- 曲単位の絞り込み：sl_songs/show.blade.php と全く同じシンプルな構造 --}}
                @include('database._breadcrumb', ['breadcrumbs' => [
                    ['label' => 'My Page', 'url' => route('mypage.index')],
                    ['label' => $song->title],
                ]])
                @if ($songNumber)
                    <p class="database-subtitle">#{{ $songNumber }}</p>
                @endif
                <h1 class="database-title" style="">{{ $song->title }}</h1>

                <div style="font-size: 1rem; color: rgba(255, 255, 255, 0.9); line-height: 1.8;">
                    @if ($song->artist)
                        <div style="">
                            <a href="{{ route('mypage.attendances.index', ['artist_id' => $songKind . '-' . $song->artist->id]) }}"
                                style="color: white; text-decoration: underline;">
                                {{ $song->artist->name }}
                            </a>
                        </div>
                    @endif
                </div>

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
                        @if ($filterArtist)
                            <h1 class="database-title" style="white-space: nowrap;">{{ $filterArtist->name }}</h1>
                            <p class="database-subtitle" style="margin: 4px 0 0;">すべてのセットリスト</p>
                        @elseif ($year)
                            <h1 class="database-title" style="white-space: nowrap;">{{ $year }}</h1>
                            <p class="database-subtitle" style="margin: 4px 0 0;">この年のすべてのセットリスト</p>
                        @elseif ($venue)
                            <h1 class="database-title" style="white-space: nowrap;">{{ $venue }}</h1>
                            <p class="database-subtitle" style="margin: 4px 0 0;">この会場のすべてのセットリスト</p>
                        @else
                            <h1 class="database-title" style="white-space: nowrap;">My Live Attendances</h1>
                            <p class="database-subtitle" style="margin: 4px 0 0;">すべてのセットリスト</p>
                        @endif
                    </div>
                    <div class="header-selects" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; max-width: 100%;">
                        {{-- 虫眼鏡アイコン（SP表示のみ） --}}
                        <button type="button" id="spSearchButtonMyAttendances" class="sp" onclick="var form = document.getElementById('spSearchFormMyAttendances'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                        </button>
                        <select class="year-select" name="select" onchange="if (this.value) window.location.href=this.value; else window.location.href='{{ route('mypage.attendances.index', array_filter(['year' => $year])) }}';">
                            <option value="" {{ $artistId ? '' : 'selected' }}>All Artists</option>
                            @foreach ($officialArtists as $artist)
                                @php $ref = 'official-' . $artist->id; @endphp
                                <option value="{{ route('mypage.attendances.index', array_filter(['artist_id' => $ref, 'year' => $year])) }}" {{ $artistId === $ref ? 'selected' : '' }}>{{ $artist->name }}</option>
                            @endforeach
                            @foreach ($myArtists as $artist)
                                @php $ref = 'user-' . $artist->id; @endphp
                                <option value="{{ route('mypage.attendances.index', array_filter(['artist_id' => $ref, 'year' => $year])) }}" {{ $artistId === $ref ? 'selected' : '' }}>{{ $artist->name }}</option>
                            @endforeach
                        </select>
                        <select class="year-select" name="select" onchange="if (this.value) window.location.href=this.value; else window.location.href='{{ route('mypage.attendances.index', array_filter(['artist_id' => $artistId])) }}';">
                            <option value="" {{ $year ? '' : 'selected' }}>All Years</option>
                            @foreach ($years as $y)
                                <option value="{{ route('mypage.attendances.index', array_filter(['artist_id' => $artistId, 'year' => $y])) }}" {{ (string)$year === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0; display: none;">
                        @livewire('my-page-song-search')
                    </div>
                </div>

                {{-- 検索フォーム（SP表示） --}}
                <div class="sp" id="spSearchFormMyAttendances" style="margin-top: 15px; display: none;">
                    @livewire('my-page-song-search')
                </div>
            @endif
        </div>
    </div>

    <div class="container-lg database-year-content">
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
                                $isOfficial = (bool) $attendance->db_setlist_id;
                                $tour = $attendance->attendedTour;
                                $isFes = in_array((int) ($tour?->type ?? 0), [2, 3, 4], true);
                                $artistRef = $tour?->artist ? ($isOfficial ? 'official' : 'user') . '-' . $tour->artist->id : null;
                            @endphp
                            <tr>
                                <td></td>
                                <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                @if ($tour?->artist && !$isFes)
                                    <td class="pc">
                                        <a href="{{ route('mypage.attendances.index', ['artist_id' => $artistRef]) }}">{{ $tour->artist->name }}</a>
                                    </td>
                                    <td class="sp">
                                        <a href="{{ route('mypage.attendances.index', ['artist_id' => $artistRef]) }}">{{ $tour->artist->name }}</a>
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
                {{-- 全件表示：sl_setlists/index.blade.php（Setlistsトップ）と同じ6列構成 --}}
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th class="mobile">#</th>
                            <th class="mobile">開催日</th>
                            <th class="sp">アーティスト / タイトル</th>
                            <th class="pc">アーティスト</th>
                            <th class="pc">タイトル</th>
                            <th class="pc">会場</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $index => $attendance)
                            @php
                                $isOfficial = (bool) $attendance->db_setlist_id;
                                $tour = $attendance->attendedTour;
                                $isFes = in_array((int) ($tour?->type ?? 0), [2, 3, 4], true);
                                $artistRef = $tour?->artist ? ($isOfficial ? 'official' : 'user') . '-' . $tour->artist->id : null;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                @if ($tour?->artist && !$isFes)
                                    <td class="sp">
                                        <a href="{{ route('mypage.attendances.index', ['artist_id' => $artistRef]) }}">{{ $tour->artist->name }}</a>
                                        /
                                        <a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'attendances']) }}">{{ $tour->title ?? '-' }}</a>
                                    </td>
                                    <td class="pc td_artist">
                                        <a href="{{ route('mypage.attendances.index', ['artist_id' => $artistRef]) }}">{{ $tour->artist->name }}</a>
                                    </td>
                                @else
                                    <td class="sp">
                                        <a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'attendances']) }}">{{ $tour->title ?? '-' }}</a>
                                    </td>
                                    <td class="pc"></td>
                                @endif
                                <td class="pc">
                                    <a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'attendances']) }}">{{ $tour->title ?? '-' }}</a>
                                </td>
                                <td class="pc">{{ $attendance->venue }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif

        @if ($song)
            {{-- 前後リンク（初めて聴いた順） --}}
            <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                @if ($previousSong)
                    <a href="{{ route('mypage.attendances.index', ['song_id' => $songKind . '-' . $previousSong->id]) }}" rel="prev"
                       style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                        <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                        Previous
                    </a>
                @else
                    <div></div>
                @endif
                @if ($nextSong)
                    <a href="{{ route('mypage.attendances.index', ['song_id' => $songKind . '-' . $nextSong->id]) }}" rel="next"
                       style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                        Next
                        <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                    </a>
                @endif
            </div>
        @endif
    </div>
@endsection
