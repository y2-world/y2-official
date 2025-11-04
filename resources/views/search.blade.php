@extends('layouts.app')
@section('title', 'Yuki Official - Search : ' . $keyword)
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <p class="database-subtitle" style="margin-bottom: 10px;">楽曲検索結果</p>
            <h1 class="database-title sp" style="margin-bottom: 20px; cursor: pointer;" onclick="document.getElementById('spSearchForm').style.display='block'; this.style.display='none';">{{ $keyword }}</h1>
            <h1 class="database-title pc" style="margin-bottom: 20px;">{{ $keyword }}</h1>

            @if ($data->isEmpty())
                <p class="database-subtitle" style="margin-bottom: 0;">検索結果がありません</p>
            @else
                <p class="database-subtitle" style="margin-bottom: 0;">全{{ count($data) }}件</p>
            @endif

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchForm" style="margin-top: 30px; display: none;">
                <form action="{{ url('/search') }}" method="GET" id="artistSearchFormSp">
                    <input type="hidden" name="match_type" value="exact">

                    {{-- 検索ワード編集 --}}
                    <div style="margin-bottom: 15px;">
                        <div class="search-wrapper">
                            <input type="text" name="keyword" id="keyword-sp" class="database-search-input" placeholder="楽曲を検索..." value="{{ request('keyword') }}" style="font-size: 14px; padding: 12px 45px 12px 16px;" list="song-suggestions-sp">
                             <datalist id="song-suggestions-sp">
                                 @foreach($suggestions as $suggestion)
                                     <option value="{{ $suggestion['title'] }}" label="{{ $suggestion['title'] }} — {{ $suggestion['artist_name'] ?? 'Unknown' }}"></option>
                                 @endforeach
                             </datalist>
                            <button type="submit" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                                <i class="fa-solid fa-magnifying-glass search-icon" style="position: static; transform: none; font-size: 16px;"></i>
                            </button>
                        </div>
                    </div>

                    {{-- アーティスト選択 --}}
                    <select name="artist_id" class="year-select" style="width: 100%; max-width: 300px; margin: 0 auto;" onchange="document.getElementById('artistSearchFormSp').submit()">
                        <option value="">全アーティスト</option>
                        @foreach($artists as $artist)
                            <option value="{{ $artist->id }}" {{ request('artist_id') == $artist->id ? 'selected' : '' }}>
                                {{ $artist->name }}
                            </option>
                        @endforeach
                    </select>

                    {{-- 閉じるボタン --}}
                    <div style="text-align: center; margin-top: 15px;">
                        <button type="button" onclick="document.getElementById('spSearchForm').style.display='none'; document.querySelector('.database-title.sp').style.display='block';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px 20px; border-radius: 20px; cursor: pointer; font-size: 13px;">
                            閉じる
                        </button>
                    </div>
                </form>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <form action="{{ url('/search') }}" method="GET">
                    <input type="hidden" name="match_type" value="exact">

                    {{-- アーティスト選択 --}}
                    <div style="margin-bottom: 20px;">
                        <select name="artist_id" class="year-select" style="width: 100%; max-width: 300px; margin: 0 auto;">
                            <option value="">全アーティスト</option>
                            @foreach($artists as $artist)
                                <option value="{{ $artist->id }}" {{ request('artist_id') == $artist->id ? 'selected' : '' }}>
                                    {{ $artist->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="search-wrapper">
                        <input type="text" name="keyword" id="keyword-pc" class="database-search-input" placeholder="楽曲を検索..." value="{{ request('keyword') }}" list="song-suggestions-pc">
                         <datalist id="song-suggestions-pc">
                             @foreach($suggestions as $suggestion)
                                 <option value="{{ $suggestion['title'] }}" label="{{ $suggestion['title'] }} — {{ $suggestion['artist_name'] ?? 'Unknown' }}"></option>
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
        @if (!$data->isEmpty())
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
                    @foreach ($data as $result)
                        <tr>
                            <td></td>
                            <td class="td_search_date">{{ date('Y.m.d', strtotime($result->date)) }}</td>
                            <td class="td_search_title"><a
                                    href="{{ route('setlists.show', $result->id) }}">{{ $result->title }}</a></td>
                            <td class="pc"><a
                                    href="{{ url('/venue?keyword=' . urlencode($result->venue)) }}">{{ $result->venue }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <br>
        @endif
        {{-- <div class=”pagination”>
            {!! $data->links() !!}
        </div> --}}
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('/js/search.js?time=' . time()) }}"></script>
    <script>
        // 検索候補のデータマップを作成
        const songMap = {
            @foreach($suggestions as $suggestion)
                "{{ $suggestion['title'] }}": {{ $suggestion['id'] }},
            @endforeach
        };

        // SP版の入力フィールド
        const keywordInputSp = document.getElementById('keyword-sp');
        if (keywordInputSp) {
            keywordInputSp.addEventListener('change', function(e) {
                const selectedTitle = e.target.value;
                if (songMap[selectedTitle]) {
                    // 候補から選択された場合は詳細ページへ
                    window.location.href = '/setlist-songs/' + songMap[selectedTitle];
                }
            });
        }

        // PC版の入力フィールド
        const keywordInputPc = document.getElementById('keyword-pc');
        if (keywordInputPc) {
            keywordInputPc.addEventListener('change', function(e) {
                const selectedTitle = e.target.value;
                if (songMap[selectedTitle]) {
                    // 候補から選択された場合は詳細ページへ
                    window.location.href = '/setlist-songs/' + songMap[selectedTitle];
                }
            });
        }
    </script>
@endsection
