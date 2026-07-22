@extends('layouts.app')
@section('title', 'Yuki Official - Venue : ' . $keyword)
@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'Setlists', 'url' => '/setlists'],
                ['label' => $keyword],
            ]])
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title" style="white-space: nowrap;">{{ $keyword }}</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">
                        @if ($data->isEmpty())
                            会場検索結果・検索結果がありません
                        @else
                            会場検索結果・全{{ count($data) }}件
                        @endif
                    </p>
                </div>
                <div class="header-selects" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; max-width: 100%;">
                    {{-- 虫眼鏡アイコン（SP表示のみ） --}}
                    <button type="button" id="spSearchButtonVenue" class="sp" onclick="var form = document.getElementById('spSearchFormVenue'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                    </button>
                </div>
                <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0; display: none;">
                    @livewire('venue-search')
                </div>
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormVenue" style="margin-top: 15px; display: none;">
                @livewire('venue-search')
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
