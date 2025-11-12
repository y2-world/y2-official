@extends('layouts.app')
@section('title', 'Yuki Official - Venue : ' . $keyword)
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <p class="database-subtitle sp" style="margin-bottom: 10px;">会場検索結果</p>
            <p class="database-subtitle pc" style="margin-bottom: 10px;">会場検索結果</p>
            <h1 class="database-title sp" style="margin-bottom: 20px; cursor: pointer;" onclick="document.getElementById('spSearchFormVenue').style.display='block'; this.style.display='none'; document.querySelectorAll('.database-subtitle.sp').forEach(el => el.style.display='none');">{{ $keyword }}</h1>
            <h1 class="database-title pc" style="margin-bottom: 20px;">{{ $keyword }}</h1>

            @if ($data->isEmpty())
                <p class="database-subtitle sp" style="margin-bottom: 0;">検索結果がありません</p>
                <p class="database-subtitle pc" style="margin-bottom: 0;">検索結果がありません</p>
            @else
                <p class="database-subtitle sp" style="margin-bottom: 0;">全{{ count($data) }}件</p>
                <p class="database-subtitle pc" style="margin-bottom: 0;">全{{ count($data) }}件</p>
            @endif

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormVenue" style="margin-top: 30px; display: none;">
                <div>
                    @livewire('venue-search')
                    {{-- 閉じるボタン --}}
                    <div style="text-align: center; margin-top: 15px;">
                        <button type="button" onclick="document.getElementById('spSearchFormVenue').style.display='none'; document.querySelector('.database-title.sp').style.display='block'; document.querySelectorAll('.database-subtitle.sp').forEach(el => el.style.display='block');" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <div>
                    @livewire('venue-search')
                </div>
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
