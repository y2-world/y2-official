@extends('layouts.app')
@section('content')
    <br>
    <div class="container-lg">
        <h2>{{ $bio->year }}</h2>
        <div class="parts-wrapper">
            <div class="dropdown-wrapper">
                <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/singles') }}" role="button">Singles</a>
                <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/albums') }}" role="button">Albums</a>
                <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/live') }}" role="button">Live</a>
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($bios as $bio)
                        <option value="{{ url('database/years', $bio->year) }}">{{ $bio->year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <br>
        <div class="setlist-row">
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
                        <th class="mb_list">#</th>
                        <th class="mb_list">タイトル</th>
                        <th class="mb_list">シングル / アルバム</th>
                        <th class="pc_list">リリース日</th>
                    </tr>
                </thead>
                <h5>Songs</h5>
                <tbody>
                    @foreach ($songs as $song)
                        <tr>
                            <td>{{ $song->id }}</td>
                            <td><a href="{{ route('songs.show', $song->id) }}">{{ $song->title }}</a></td>
                            @if (isset($song->single_id) && isset($song->album_id))
                                @if ($song->single->date > $song->album->date)
                                    <td><a href="{{ route('albums.show', $song->album_id) }}">{{ $song->album->title }}</a>
                                    </td>
                                    <td class="pc_list">{{ date('Y.m.d', strtotime($song->album->date)) }}</td>
                                @else
                                    <td><a
                                            href="{{ route('singles.show', $song->single_id) }}">{{ $song->single->title }}</a>
                                    </td>
                                    <td class="pc_list">{{ date('Y.m.d', strtotime($song->single->date)) }}</td>
                                @endif
                            @elseif(isset($song->album_id))
                                <td><a href="{{ route('albums.show', $song->album_id) }}">{{ $song->album->title }}</a>
                                </td>
                                <td class="pc_list">{{ date('Y.m.d', strtotime($song->album->date)) }}</td>
                            @elseif(isset($song->single_id))
                                <td><a href="{{ route('singles.show', $song->single_id) }}">{{ $song->single->title }}</a>
                                </td>
                                <td class="pc_list">{{ date('Y.m.d', strtotime($song->single->date)) }}</td>
                            @else
                                <td></td>
                                <td class="pc_list"></td>
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
        <h5>Live</h5>
        @if (!$tours->isEmpty())
            <table class="table table-striped count">
                <thead>
                    <tr>
                        <th class="bios_list">#</th>
                        <th class="bios_list">開催日</th>
                        <th class="bios_list">タイトル</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $hasTourEvent = false;
                        $hasSolo = false;
                    @endphp

                    @foreach ($tours as $tour)
                        @if ($tour->type != 4)
                            @php $hasTourEvent = true; @endphp
                        @else
                            @php $hasSolo = true; @endphp
                        @endif
                    @endforeach

                    @if ($hasTourEvent)
                        <h6>Tour / Event</h6>
                        @foreach ($tours as $tour)
                            @if ($tour->type != 4)
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
                    @endif

                    @if ($hasSolo)
                        <h6>Solo</h6>
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
                    @endif
                </tbody>
            </table>
            <br>
        @endif
    </div>
    </div>
    </div>
@endsection
