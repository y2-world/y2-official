@extends('layouts.app')
@section('title', 'Yuki Official - ' . $year->year)
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <h1 class="database-title">{{ $year->year }}</h1>
            <p class="database-subtitle">この年のすべてのセットリスト</p>

            <div class="year-navigation">
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Artists</option>
                    @foreach ($artists as $artist)
                        <option value="{{ url('/setlists/artists', $artist->id) }}">{{ $artist->name }}</option>
                    @endforeach
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($years as $year)
                        <option value="{{ url('/setlists/years', $year->year) }}">{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <form action="{{ url('/search') }}" method="GET" id="year-search-form">
                    <input type="hidden" name="match_type" value="partial">
                    <div class="search-wrapper">
                        <input type="text" name="keyword" id="keyword-year" class="database-search-input" placeholder="楽曲を検索..." value="{{ request('keyword') }}" list="song-suggestions-year">
                        <datalist id="song-suggestions-year">
                            @foreach($suggestions as $suggestion)
                                <option value="{{ $suggestion['title'] }}"></option>
                            @endforeach
                        </datalist>
                        <button type="submit" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fa-solid fa-magnifying-glass search-icon" style="position: static; transform: none;"></i>
                        </button>
                    </div>
                </form>
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
<script src="{{ asset('/js/search.js?time=' . time()) }}"></script>
<script>
    // 検索候補のデータマップを作成
    const songMapYear = {
        @foreach($suggestions as $suggestion)
            "{{ $suggestion['title'] }}": {{ $suggestion['id'] }},
        @endforeach
    };

    // 検索フォームの入力フィールド
    const keywordInputYear = document.getElementById('keyword-year');
    if (keywordInputYear) {
        keywordInputYear.addEventListener('change', function(e) {
            const selectedTitle = e.target.value;
            if (songMapYear[selectedTitle]) {
                // 候補から選択された場合は詳細ページへ
                window.location.href = '/setlist-songs/' + songMapYear[selectedTitle];
            }
        });
    }
</script>
@endsection
