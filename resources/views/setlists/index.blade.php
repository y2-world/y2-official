@php
    // 現在のページに基づく基点番号を計算
    $startNumber = ($setlists->currentPage() - 1) * $setlists->perPage() + 1;
@endphp
@extends('layouts.app')
@section('content')
    <br>
    <div class="container-lg">
        <h2>Set Lists</h2>
        <div class="parts-wrapper">
            <div class="dropdown-wrapper">
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Artists</option>
                    @foreach ($artists as $artist)
                        <option value="{{ url('/setlists/artists', $artist->id) }}">{{ $artist->name }}</option>
                    @endforeach
                </select>
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($years as $year)
                        <option value="{{ url('/setlists/years', $year->year) }}">{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pc">
                <div class="search">
                    <form action="{{ url('/search') }}" method="GET">
                        <div class="mb_dropdown">
                            <select name="artist_id" required data-toggle="select">
                                <option value="" disabled selected>Artists</option>
                                <option value="">アーティスト指定なし</option>
                                @foreach ($allArtists as $artist)
                                    <option value="{{ $artist->id }}" required>{{ $artist->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="input-group mb-3" style="width: 400px;">
                            <input type="search" class="form-control" aria-label="Search" value="{{ request('keyword') }}"
                                name="keyword" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="submit" id="button-addon2"><i
                                        class="fa-solid fa-magnifying-glass"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <ul class="music-menu">
            <li><a href="{{ url('/setlists') }}" class="{{ request('type') ? '' : 'active' }}">All</a></li>
            <li><a href="{{ url('/setlists?type=1') }}" class="{{ request('type') == '1' ? 'active' : '' }}">Live</a></li>
            <li><a href="{{ url('/setlists?type=2') }}" class="{{ request('type') == '2' ? 'active' : '' }}">Fes</a></li>
        </ul>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mobile">#</th>
                    <th class="mobile">開催日</th>
                    @if (request('type') != 2)
                        <th class="sp">アーティスト / タイトル</th>
                        <th class="pc">アーティスト</th>
                    @endif
                    @if (request('type') == 2)
                        <th class="pc"></th>
                        <th class="sp">タイトル</th>
                        <th class="pc">タイトル</th>
                    @else
                        <th class="pc">タイトル</th>
                    @endif
                    <th class="pc">会場</th>
                </tr>
            </thead>
            <div class="all-setlist">
                <tbody>
                    @php
                        // 現在ページでの開始番号を計算（逆順用）
                        $startNumber = $totalCount - ($setlists->currentPage() - 1) * $setlists->perPage();
                    @endphp
                    @foreach ($setlists as $index => $setlist)
                        <tr>
                            <td>{{ $startNumber - $index }}</td>
                            <td>{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
                            @if (isset($setlist->artist_id))
                                <td class="pc">
                                    <a
                                        href="{{ url('/setlists/artists', $setlist->artist_id) }}">{{ $setlist->artist->name }}</a>
                                </td>
                                <td class="sp">
                                    <a
                                        href="{{ url('/setlists/artists', $setlist->artist_id) }}">{{ $setlist->artist->name }}</a>
                                    /
                                    <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                                </td>
                            @else
                                <td class="pc"></td>
                                <td class="sp">
                                    <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                                </td>
                            @endif
                            <td class="pc">
                                <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                            </td>
                            <td class="pc">
                                <a
                                    href="{{ url('/venue?keyword=' . urlencode($setlist->venue)) }}">{{ $setlist->venue }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </div>
        </table>
        <div class=”pagination”>
            {!! $setlists->appends(['type' => $type])->links() !!}
        </div>
        <br>
    </div>
@endsection
