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
                <div>
                    <div class="search-wrapper">
                        <input type="text" name="keyword" id="keyword-year" class="database-search-input typeahead" placeholder="楽曲を検索..." value="{{ request('keyword') }}" required>
                    </div>
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
<script src="{{ asset('/js/search.js?time=' . time()) }}"></script>
<script>
    // Typeaheadの初期化関数
    function initTypeaheadYear(inputId) {
        const $input = $(inputId);
        if ($input.length && !$input.data('typeahead')) {
            var songs = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                remote: {
                    url: '/find-setlist-song?q=%QUERY',
                    wildcard: '%QUERY'
                },
                limit: 20
            });

            $input.typeahead({
                minLength: 1,
                highlight: true,
                hint: true,
                classNames: {
                    menu: 'tt-menu-modern',
                    suggestion: 'tt-suggestion-modern',
                    cursor: 'tt-cursor-modern'
                }
            },
            {
                name: 'songs',
                display: 'title',
                source: songs,
                limit: 20,
                templates: {
                    empty: '<div class="tt-empty">該当する曲が見つかりません</div>',
                    suggestion: function(data) {
                        var artistText = data.artist ? ' - ' + data.artist : '';
                        return '<div class="tt-suggestion-content"><span>' + data.title + artistText + '</span></div>';
                    }
                }
            }).on('typeahead:selected', function(event, data) {
                window.location.href = '/setlist-songs/' + data.id;
            });
        }
    }

    $(document).ready(function() {
        setTimeout(function() {
            initTypeaheadYear('#keyword-year');
        }, 100);
    });
</script>
@endsection
