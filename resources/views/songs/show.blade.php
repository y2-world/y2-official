@extends('layouts.app')
@section('content')
    <br>
    <div class="container-lg">
       
            <div class="song-wrapper">
                <div class="dropdown-wrapper">
                    <div class="setlist"># {{ $songs->id }}</div>
                    <h3> {{ $songs->title }}</h3>
                    <div class="setlist">
                        @if (isset($songs->single->title))
                            @if (isset($songs->single->title))
                                Single : <a href="{{ route('singles.show', $songs->single_id) }}">{{ $songs->single->title }}</a>
                                </td>
                            @endif
                            <br>
                            @if (isset($songs->single->date))
                                Release Date : {{ date('Y.m.d', strtotime($songs->single->date)) }}<br>
                            @endif
                        @endif
                        @if (isset($songs->album->title))
                            @if (isset($songs->album->title))
                                Album : <a href="{{ route('albums.show', $songs->album_id) }}">{{ $songs->album->title }}</a>
                                </td>
                            @endif
                            <br>
                            @if (isset($songs->album->date))
                                Release Date : {{ date('Y.m.d', strtotime($songs->album->date)) }}
                            @endif
                        @else
                            アルバム未収録
                        @endif
                        {{ $songs->text }}
                    </div>
                </div>
                <div class="search">
                    <form action="" method="GET">
                        <div class="input-group">
                            <input type="text" id="searchInput" class="form-control typeahead" placeholder="Search" style="width: 350px;" required>
                        </div>
                    </form>
                </div>
            </div>

            @if (!$tours->isEmpty())
                <br>
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
                                @endif
                                <td class="td_title"><a
                                        href="{{ route('live.show', $tour->id) }}">{{ $tour->title }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="show_button">
                @if (isset($previous))
                    <a class="btn btn-outline-dark" href="{{ route('songs.show', $previous->id) }}" rel="prev"
                        role="button">
                        <</a>
                @endif
                @if (isset($next))
                    <a class="btn btn-outline-dark" href="{{ route('songs.show', $next->id) }}"rel="next"
                        role="button">></a>
                @endif
            </div>
    </div>
@endsection
