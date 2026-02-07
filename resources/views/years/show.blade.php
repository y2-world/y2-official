@extends('layouts.app')
@section('title', 'Yuki Official - ' . $year->year)

@section('og_title', $year->year . ' Setlists - Yuki Official')
@section('og_description', 'All setlists from ' . $year->year)
@section('og_type', 'website')

@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <h1 class="database-title">{{ $year->year }}</h1>
            <p class="database-subtitle">この年のすべてのセットリスト</p>

            <div class="year-navigation">
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Artists</option>
                    @foreach ($artists as $artistItem)
                        <option value="{{ url('/setlists/artists', $artistItem->id) }}">{{ $artistItem->name }}</option>
                    @endforeach
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($years as $yearItem)
                        <option value="{{ url('/setlists/years', $yearItem->year) }}">{{ $yearItem->year }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <div>
                    @livewire('song-search')
                </div>
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">
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
                @foreach ($setlists as $setlist)
                    <tr>
                        <td></td>
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
                            <td class="sp"><a
                                    href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a></td>
                        @endif
                        <td class="pc"><a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                        </td>
                        <td class="pc"><a
                                href="{{ url('/venue?keyword=' . urlencode($setlist->venue)) }}">{{ $setlist->venue }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <br>
    </div>
    </div>
@endsection

@section('page-script')
@endsection
