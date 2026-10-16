@extends('layouts.app')
@section('title', 'Yuki Official - ' . $title)
@section('content')
    {{-- 登録していない曲（セットリストに名前だけで入っている曲）：その曲が入っているライブの一覧（楽曲検索結果）。会場のページと同じ形 --}}
    <div class="database-hero database-hero--nav">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'Database', 'url' => '/database'],
                ['label' => $artist->name, 'url' => route('database.artist', $artist->id)],
                ['label' => 'Songs', 'url' => route('database.songs', $artist->id)],
                ['label' => $title],
            ]])
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title">{{ $title }}</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">
                        @if ($setlists->isEmpty())
                            楽曲検索結果・0件
                        @else
                            楽曲検索結果・全{{ $setlists->pluck('tour_id')->unique()->count() }}件
                        @endif
                    </p>
                </div>
                <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0;">
                    @livewire('database-song-search', ['artistId' => $artist->id])
                </div>
            </div>
            <div class="sp" style="margin-top: 15px;">
                @livewire('database-song-search', ['artistId' => $artist->id])
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">
        @if (!$setlists->isEmpty())
            <table class="table table-striped count fixed-cols">
                <thead>
                    <tr>
                        <th class="mobile col-no">#</th>
                        <th class="mobile col-date">開催日</th>
                        <th>タイトル</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($setlists->groupBy('tour_id') as $tourSetlists)
                        @php $tour = $tourSetlists->first()->tour; @endphp
                        <tr>
                            <td></td>
                            <td class="td_search_date">{{ $tour?->date1 ? date('Y.m.d', strtotime($tour->date1)) : '' }}</td>
                            <td>
                                <a href="{{ route('live.show', $tour->id) }}">{{ $tour->title }}</a>
                                {{-- 一部のパターンにだけ入っているときは、そのパターン（公演）を小さく出す --}}
                                @if ($tourSetlists->count() < \App\Models\DbSetlist::where('tour_id', $tour->id)->count())
                                    <br><small style="color: #888;">{{ $tourSetlists->pluck('subtitle')->filter()->implode(' / ') }}</small>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
