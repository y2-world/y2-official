@extends('layouts.app')
@section('title', 'Yuki Official - Venue : ' . $keyword)
@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => "Yuki's Setlists", 'url' => '/setlists'],
                ['label' => $keyword],
            ]])
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title" style="white-space: nowrap;">{{ $keyword }}</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">
                        @if ($data->isEmpty())
                            この会場のセットリストはありません
                        @else
                            すべてのセットリスト・全{{ count($data) }}件
                        @endif
                    </p>
                </div>
                <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0; display: none;">
                    @livewire('venue-search')
                </div>
            </div>

            {{-- 虫眼鏡アイコン（SP表示のみ、見出しブロックの右下）：押すとフォームが開き、ボタン自体は隠れる（曲のページと同じ） --}}
            <button type="button" id="spSearchButtonVenue" class="sp" onclick="document.getElementById('spSearchButtonVenue').style.display='none'; document.getElementById('spSearchFormVenue').style.display='block';" style="position: absolute; bottom: -14px; right: 8px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
            </button>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormVenue" style="margin-top: 10px; display: none;">
                <div>
                    @livewire('venue-search')
                    {{-- 閉じるボタン --}}
                    <div style="text-align: center; margin-top: 15px;">
                        <button type="button" onclick="document.getElementById('spSearchFormVenue').style.display='none'; document.getElementById('spSearchButtonVenue').style.display='flex';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">
        @if (!$data->isEmpty())
            <table class="table table-striped count fixed-cols">
                <thead>
                    <tr>
                        <th class="mobile col-no">#</th>
                        <th class="mobile col-date">開催日</th>
                        <th class="pc col-artist">アーティスト</th>
                        <th class="sp">アーティスト / タイトル</th>
                        <th class="pc">タイトル</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $result)
                        <tr>
                            <td></td>
                            <td class="td_search_date">{{ date('Y.m.d', strtotime($result->date)) }}</td>
                            @if ($result->artist_name)
                            <td class="pc">
                                @if ($result->artist_url)
                                    <a href="{{ $result->artist_url }}">{{ $result->artist_name }}</a>
                                @else
                                    {{ $result->artist_name }}
                                @endif
                            </td>
                            <td class="sp">
                                @if ($result->artist_url)
                                    <a href="{{ $result->artist_url }}">{{ $result->artist_name }}</a>
                                @else
                                    {{ $result->artist_name }}
                                @endif
                                /
                                <a href="{{ $result->url }}">{{ $result->title }}</a>
                            </td>
                        @else
                            <td class="pc"></td>
                            <td class="sp"><a href="{{ $result->url }}">{{ $result->title }}</a></td>
                        @endif
                        <td class="pc"><a href="{{ $result->url }}">{{ $result->title }}</a>
                        </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <br>
        @endif
        {{-- <div class=”pagination”>
            {!! $data->links() !!}
        </div> --}}
    </div>
@endsection

@section('page-script')
@endsection
