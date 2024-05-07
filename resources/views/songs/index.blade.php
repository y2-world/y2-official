@extends('layouts.app')
@section('content')
    <br>
    <div class="container-lg">
        <h2>Songs</h2>
        <div class="database-wrapper">
            <div class="dropdown-wrapper">
                <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/singles') }}" role="button">Singles</a>
                <a class="btn btn-outline-dark btn-sm" href="{{ url('/database/albums') }}" role="button">Albums</a>
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Live</option>
                    <option value="{{ url('/database/live') }}">All</option>
                    <option value="{{ url('/database/live?type=1') }}">Tours</option>
                    <option value="{{ url('/database/live?type=2') }}">Events</option>
                    <option value="{{ url('/database/live?type=3') }}">ap bank fes</option>
                    <option value="{{ url('/database/live?type=4') }}">Solo</option>
                </select>
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($bios as $bio)
                        <option value="{{ url('/database/years', $bio->year) }}">{{ $bio->year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mobile">#</th>
                    <th class="mobile">タイトル</th>
                    <th class="mobile">シングル / アルバム</th>
                    <th class="pc">リリース日</th>
                </tr>
            </thead>
            <div class="all-setlist">
                <tbody>
                    @foreach ($songs as $song)
                        <tr>
                            <td>{{ $song->id }}</td>
                            <td><a href="{{ route('songs.show', $song->id) }}">{{ $song->title }}</a></td>
                            @if (isset($song->single_id) && isset($song->album_id))
                                @if ($song->single->date > $song->album->date)
                                    <td><a href="{{ route('albums.show', $song->album_id) }}">{{ $song->album->title }}</a>
                                    </td>
                                    <td class="pc">{{ date('Y.m.d', strtotime($song->album->date)) }}</td>
                                @else
                                    <td><a
                                            href="{{ route('singles.show', $song->single_id) }}">{{ $song->single->title }}</a>
                                    </td>
                                    <td class="pc">{{ date('Y.m.d', strtotime($song->single->date)) }}</td>
                                @endif
                            @elseif(isset($song->album_id))
                                <td><a href="{{ route('albums.show', $song->album_id) }}">{{ $song->album->title }}</a>
                                </td>
                                <td class="pc">{{ date('Y.m.d', strtotime($song->album->date)) }}</td>
                            @elseif(isset($song->single_id))
                                <td><a href="{{ route('singles.show', $song->single_id) }}">{{ $song->single->title }}</a>
                                </td>
                                <td class="pc">{{ date('Y.m.d', strtotime($song->single->date)) }}</td>
                            @else
                                <td></td>
                                <td class="pc"></td>
                            @endif

                        </tr>
                    @endforeach
                </tbody>
            </div>
        </table>
        <div class=”pagination”>
            {!! $songs->links() !!}
        </div>
        <br>
    </div>
@endsection
