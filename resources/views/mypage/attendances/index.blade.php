@extends('layouts.app')
@section('title', 'Yuki Official - 参加記録一覧')

@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container">
            @if ($song)
                @include('database._breadcrumb', ['breadcrumbs' => [
                    ['label' => 'My Page', 'url' => route('mypage.index')],
                    ['label' => $song->title],
                ]])
            @endif
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    @if ($song)
                        <p class="database-subtitle" style="margin: 0;">#{{ $songNumber }}</p>
                        <h1 class="database-title sp" style="cursor: pointer;"
                            onclick="document.getElementById('spSearchFormMyPageSong').style.display='block'; document.querySelector('.database-title.sp').style.display='none';">
                            {{ $song->title }}
                        </h1>
                        <h1 class="database-title pc" style="">{{ $song->title }}</h1>
                        @if ($song->artist)
                            <p class="database-subtitle" style="margin: 4px 0 0;">
                                <a href="{{ route('mypage.stats.artist', $song->artist_id) }}" style="color: inherit;">{{ $song->artist->name }}</a>
                            </p>
                        @endif
                    @elseif ($filterArtist)
                        <h1 class="database-title" style="white-space: nowrap;">{{ $filterArtist->name }}</h1>
                        <p class="database-subtitle" style="margin: 4px 0 0;">すべてのセットリスト</p>
                    @elseif ($year)
                        <h1 class="database-title" style="white-space: nowrap;">{{ $year }}</h1>
                        <p class="database-subtitle" style="margin: 4px 0 0;">この年のすべてのセットリスト</p>
                    @else
                        <h1 class="database-title" style="white-space: nowrap;">My Live Attendances</h1>
                        <p class="database-subtitle" style="margin: 4px 0 0;">すべてのセットリスト</p>
                    @endif
                </div>
                @unless ($song)
                    <div class="header-selects" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; max-width: 100%;">
                        {{-- 虫眼鏡アイコン（SP表示のみ） --}}
                        <button type="button" id="spSearchButtonMyAttendances" class="sp" onclick="var form = document.getElementById('spSearchFormMyAttendances'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                        </button>
                        <select class="year-select" name="select" onchange="if (this.value) window.location.href=this.value; else window.location.href='{{ route('mypage.attendances.index', array_filter(['year' => $year])) }}';">
                            <option value="" {{ $artistId ? '' : 'selected' }}>All Artists</option>
                            @foreach ($artists as $artist)
                                <option value="{{ route('mypage.attendances.index', array_filter(['artist_id' => $artist->id, 'year' => $year])) }}" {{ (string)$artistId === (string)$artist->id ? 'selected' : '' }}>{{ $artist->name }}</option>
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
                        @livewire('my-page-song-search', ['artistId' => $artistId])
                    </div>
                @endunless
            </div>

            @if ($song)
                {{-- 検索フォーム（SP表示） --}}
                <div class="sp" id="spSearchFormMyPageSong" style="margin-top: 10px; display: none;">
                    <div>
                        @livewire('my-page-song-search', ['artistId' => $song->artist_id])
                        {{-- 閉じるボタン --}}
                        <div style="text-align: center; margin-top: 15px;">
                            <button type="button" onclick="document.getElementById('spSearchFormMyPageSong').style.display='none'; document.querySelector('.database-title.sp').style.display='block';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 検索フォーム（PC表示のみ） --}}
                <div class="database-search pc song-search-top-right">
                    <div>
                        @livewire('my-page-song-search', ['artistId' => $song->artist_id])
                    </div>
                </div>
            @else
                {{-- 検索フォーム（SP表示） --}}
                <div class="sp" id="spSearchFormMyAttendances" style="margin-top: 15px; display: none;">
                    @livewire('my-page-song-search', ['artistId' => $artistId])
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
                                <td><a href="{{ route('mypage.attendances.show', $attendance) }}">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a></td>
                                <td class="pc">{{ $attendance->venue }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif ($year)
                {{-- 年単位の絞り込み：years.show と同じ構成（table.count、pc:アーティストが先） --}}
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
                            <tr>
                                <td></td>
                                <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                @if ($attendance->dbSetlist?->tour?->artist)
                                    <td class="pc">
                                        <a href="{{ route('mypage.attendances.index', ['artist_id' => $attendance->dbSetlist->tour->artist_id]) }}">{{ $attendance->dbSetlist->tour->artist->name }}</a>
                                    </td>
                                    <td class="sp">
                                        <a href="{{ route('mypage.attendances.index', ['artist_id' => $attendance->dbSetlist->tour->artist_id]) }}">{{ $attendance->dbSetlist->tour->artist->name }}</a>
                                        /
                                        <a href="{{ route('mypage.attendances.show', $attendance) }}">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a>
                                    </td>
                                @else
                                    <td class="pc"></td>
                                    <td class="sp"><a href="{{ route('mypage.attendances.show', $attendance) }}">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a></td>
                                @endif
                                <td class="pc"><a href="{{ route('mypage.attendances.show', $attendance) }}">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a></td>
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
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                @if ($attendance->dbSetlist?->tour?->artist)
                                    <td class="sp">
                                        <a href="{{ route('mypage.attendances.index', ['artist_id' => $attendance->dbSetlist->tour->artist_id]) }}">{{ $attendance->dbSetlist->tour->artist->name }}</a>
                                        /
                                        <a href="{{ route('mypage.attendances.show', $attendance) }}">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a>
                                    </td>
                                    <td class="pc td_artist">
                                        <a href="{{ route('mypage.attendances.index', ['artist_id' => $attendance->dbSetlist->tour->artist_id]) }}">{{ $attendance->dbSetlist->tour->artist->name }}</a>
                                    </td>
                                @else
                                    <td class="sp">
                                        <a href="{{ route('mypage.attendances.show', $attendance) }}">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a>
                                    </td>
                                    <td class="pc"></td>
                                @endif
                                <td class="pc">
                                    <a href="{{ route('mypage.attendances.show', $attendance) }}">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a>
                                </td>
                                <td class="pc">{{ $attendance->venue }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            {{ $attendances->links() }}
        @endif
    </div>
@endsection
