@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' ' . $bio->year)
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <h1 class="database-title">{{ $bio->year }}</h1>
            <p class="database-subtitle">この年のすべての楽曲とライブ情報</p>

            <div class="year-navigation" style="display: flex; align-items: center; gap: 10px;">
                {{-- 虫眼鏡アイコン（SP表示のみ・ドロップダウンの左端） --}}
                <button type="button" id="spSearchButtonDatabaseYear" class="sp" onclick="var form = document.getElementById('spSearchFormDatabaseYear'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                </button>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Discography</option>
                    <option value="{{ route('database.singles', $artist->id) }}">Singles</option>
                    <option value="{{ route('database.albums', $artist->id) }}">Albums</option>
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Live</option>
                    <option value="{{ route('database.live', $artist->id) }}">All</option>
                    <option value="{{ route('database.live', $artist->id) }}?type=1">Tours</option>
                    <option value="{{ route('database.live', $artist->id) }}?type=2">Events</option>
                    @if($artist->name === 'Mr.Children')
                    <option value="{{ route('database.live', $artist->id) }}?type=3">ap bank fes</option>
                    <option value="{{ route('database.live', $artist->id) }}?type=4">Solo</option>
                    @endif
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($bios as $b)
                        <option value="{{ route('database.biography.year', [$artist->id, $b->year]) }}">{{ $b->year }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormDatabaseYear" style="margin-top: 20px; display: none;">
                <div>
                    @livewire('database-song-search', ['artistId' => $artist->id])
                </div>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <div>
                    @livewire('database-song-search', ['artistId' => $artist->id])
                </div>
            </div>
        </div>
    </div>

    <div class="container database-year-content">
        <div class="database-row">
            @if (!$songs->isEmpty())
                @if ($tours->isEmpty())
                    <div class="column">
                    @else
                        <div class="column1">
                @endif
                @if ($tours->isEmpty())
                    <table class="table table-striped">
                    @else
                        <table class="table table-striped songs">
                @endif
                <thead>
                    <tr>
                        <th class="mobile">#</th>
                        <th class="mobile">タイトル</th>
                        <th class="mobile">シングル / アルバム</th>
                        <th class="pc">リリース日</th>
                    </tr>
                </thead>
                <h5>Songs</h5>
                <tbody>
                    @foreach ($songs as $song)
                        @php
                            $single = $song->singleFromTracklist;
                            $album = $song->albumFromTracklist;
                        @endphp
                        <tr>
                            <td>{{ $song->id }}</td>
                            <td><a href="{{ route('songs.show', $song->id) }}">{{ $song->title }}</a></td>
                            @if ($single && $album)
                                @if ($single->date > $album->date)
                                    <td><a href="{{ route('albums.show', $album->id) }}">{{ $album->title }}</a></td>
                                    <td class="pc">{{ date('Y.m.d', strtotime($album->date)) }}</td>
                                @else
                                    <td><a href="{{ route('singles.show', $single->id) }}">{{ $single->title }}</a></td>
                                    <td class="pc">{{ date('Y.m.d', strtotime($single->date)) }}</td>
                                @endif
                            @elseif($album)
                                <td><a href="{{ route('albums.show', $album->id) }}">{{ $album->title }}</a></td>
                                <td class="pc">{{ date('Y.m.d', strtotime($album->date)) }}</td>
                            @elseif($single)
                                <td><a href="{{ route('singles.show', $single->id) }}">{{ $single->title }}</a></td>
                                <td class="pc">{{ date('Y.m.d', strtotime($single->date)) }}</td>
                            @else
                                <td></td>
                                <td class="pc"></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                </table>
            @endif
        </div>
        @if ($songs->isEmpty())
            <div class="column">
            @else
                <div class="column2-bio">
        @endif
        @if (!$tours->isEmpty())

            @php
                $hasTourEvent = false;
                $hasSolo = false;
            @endphp

            @foreach ($tours as $tour)
                @if ($tour->type == 0 || $tour->type != 4)
                    @php $hasTourEvent = true; @endphp
                @else
                    @php $hasSolo = true; @endphp
                @endif
            @endforeach

            @if ($hasTourEvent)
                <table class="table table-striped count tour">
                    <thead>
                        <tr>
                            <th class="mobile">#</th>
                            <th class="mobile">開催日</th>
                            <th class="mobile">タイトル</th>
                        </tr>
                    </thead>
                    <tbody>
                        <h5>Tour / Event</h5>
                        @foreach ($tours as $tour)
                            @if ($tour->type == 0 || $tour->type != 4)
                                <tr>
                                    <td></td>
                                    @if (isset($tour->date1) && isset($tour->date2))
                                        <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                                            {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                                    @elseif(isset($tour->date1) && !isset($tour->date2))
                                        <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                                    @endif
                                    <td class="td_title"><a
                                            href="{{ route('live.show', $tour->id) }}">{{ $tour->title }}</a></td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <br>
            @endif

            @if ($hasSolo)
                <table class="table table-striped count solo">
                    <thead>
                        <tr>
                            <th class="mobile">#</th>
                            <th class="mobile">開催日</th>
                            <th class="mobile">タイトル</th>
                        </tr>
                    </thead>
                    <tbody>
                        <h5>Solo</h5>
                        @foreach ($tours as $tour)
                            @if ($tour->type == 4)
                                <tr>
                                    <td></td>
                                    @if (isset($tour->date1) && isset($tour->date2))
                                        <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                                            {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                                    @elseif(isset($tour->date1) && !isset($tour->date2))
                                        <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                                    @endif
                                    <td class="td_title"><a
                                            href="{{ route('live.show', $tour->id) }}">{{ $tour->title }}</a></td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <br>
            @endif

        @endif
        </div>
    </div>
@endsection
