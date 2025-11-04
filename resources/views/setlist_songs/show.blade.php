@extends('layouts.app')
@section('title', 'Yuki Official - ' . $song->title)
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <p class="database-subtitle" style="margin-bottom: 10px;"># {{ $song->id }}</p>
            <h1 class="database-title" style="margin-bottom: 20px;">{{ $song->title }}</h1>

            <div style="font-size: 1rem; color: rgba(255, 255, 255, 0.9); line-height: 1.8;">
                @if ($song->artist)
                    <div style="margin-bottom: 8px;">
                        <strong>Artist:</strong>
                        <a href="{{ url('/setlists/artists', $song->artist_id) }}" style="color: white; text-decoration: underline;">
                            {{ $song->artist->name }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <div>
                    <div class="search-wrapper">
                        <input type="text" name="keyword" id="keyword-setlist-song" class="database-search-input" placeholder="楽曲を検索..." list="song-suggestions-setlist-song">
                        <datalist id="song-suggestions-setlist-song">
                            @foreach($suggestions as $suggestion)
                                <option value="{{ $suggestion['title'] }}" label="{{ $suggestion['title'] }}{{ $suggestion['artist_name'] ? ' — ' . $suggestion['artist_name'] : '' }}"></option>
                            @endforeach
                        </datalist>
                        <button type="button" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fa-solid fa-magnifying-glass search-icon" style="position: static; transform: none;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">

        @if (!$setlists->isEmpty())
            <h3 style="margin-top: 0; margin-bottom: 15px;">Live Performances</h3>
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
                    @foreach ($setlists as $setlist)
                        <tr>
                            <td></td>
                            <td class="td_date">{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
                            <td class="td_title"><a
                                    href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a></td>
                            <td class="pc"><a
                                    href="{{ url('/venue?keyword=' . urlencode($setlist->venue)) }}">{{ $setlist->venue }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- 前後リンク --}}
        <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
            @if (isset($previous))
                <a href="{{ url('/setlist-songs', $previous->id) }}" rel="prev"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                    Previous
                </a>
            @else
                <div></div>
            @endif
            @if (isset($next))
                <a href="{{ url('/setlist-songs', $next->id) }}" rel="next"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    Next
                    <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            @endif
        </div>
    </div>
@endsection

@section('page-script')
<script src="{{ asset('/js/search.js?time=' . time()) }}"></script>
<script>
    // 検索候補のデータマップを作成
    const songMapSetlistSong = {
        @foreach($suggestions as $suggestion)
            "{{ $suggestion['title'] }}": {{ $suggestion['id'] }},
        @endforeach
    };

    // 検索フォームの入力フィールド
    const keywordInputSetlistSong = document.getElementById('keyword-setlist-song');
    if (keywordInputSetlistSong) {
        // フォーム送信を防ぐ
        keywordInputSetlistSong.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const selectedTitle = e.target.value;
                if (songMapSetlistSong[selectedTitle]) {
                    window.location.href = '/setlist-songs/' + songMapSetlistSong[selectedTitle];
                }
            }
        });
        
        keywordInputSetlistSong.addEventListener('change', function(e) {
            const selectedTitle = e.target.value;
            if (songMapSetlistSong[selectedTitle]) {
                // 候補から選択された場合は詳細ページへ
                window.location.href = '/setlist-songs/' + songMapSetlistSong[selectedTitle];
            }
        });
    }
</script>
@endsection
