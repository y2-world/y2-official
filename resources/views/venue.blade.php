@extends('layouts.app')
@section('title', 'Yuki Official - Venue : ' . $keyword)
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <p class="database-subtitle" style="margin-bottom: 10px;">会場検索結果</p>
            <h1 class="database-title" style="margin-bottom: 20px;">{{ $keyword }}</h1>

            @if ($data->isEmpty())
                <p class="database-subtitle" style="margin-bottom: 0;">検索結果がありません</p>
            @else
                <p class="database-subtitle" style="margin-bottom: 0;">全{{ count($data) }}件</p>
            @endif

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <form action="{{ url('/venue') }}" method="GET">
                    <div class="search-wrapper">
                        <input type="text" name="keyword" class="database-search-input" placeholder="会場を検索..." value="{{ request('keyword') }}">
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
                        <th class="pc">アーティスト</th>
                        <th class="sp">アーティスト / タイトル</th>
                        <th class="pc">タイトル</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $result)
                        <tr>
                            <td></td>
                            <td class="td_search_date">{{ date('Y.m.d', strtotime($result->date)) }}</td>
                            @if (isset($result->artist_id))
                            <td class="pc">
                                <a href="{{ url('/setlists/artists', $result->artist_id) }}">{{ $result->artist->name }}</a>
                            </td>
                            <td class="sp">
                                <a href="{{ url('/setlists/artists', $result->artist_id) }}">{{ $result->artist->name }}</a> /
                                <a href="{{ route('setlists.show', $result->id) }}">{{ $result->title }}</a>
                            </td>
                        @else
                            <td class="pc"></td>
                            <td class="sp"><a
                                    href="{{ route('setlists.show', $result->id) }}">{{ $result->title }}</a></td>
                        @endif
                        <td class="pc"><a href="{{ route('setlists.show', $result->id) }}">{{ $result->title }}</a>
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
@endsection
