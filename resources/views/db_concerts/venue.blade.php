@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' Venue : ' . $keyword)
@section('content')
    {{-- 1つの会場の公演（スケジュールの1日ずつ）の一覧。新しい順 --}}
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'Database', 'url' => '/database'],
                ['label' => $artist->name, 'url' => route('database.artist', $artist->id)],
                ['label' => 'Venues', 'url' => route('database.venues', $artist->id)],
                ['label' => $keyword],
            ]])
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title">{{ $keyword }}</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">
                        @if ($performances->isEmpty())
                            この会場の公演はありません
                        @else
                            {{ $artist->name }} — すべての公演・全{{ count($performances) }}件
                        @endif
                    </p>
                </div>
                <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0;">
                    @livewire('venue-search', ['dbArtistId' => $artist->id], key('db-venue-search-pc'))
                </div>
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" style="margin-top: 15px;">
                @livewire('venue-search', ['dbArtistId' => $artist->id], key('db-venue-search-sp'))
            </div>
        </div>
    </div>

    <div class="container database-year-content">
        @if ($performances->isNotEmpty())
            <table class="table table-striped fixed-cols">
                <thead>
                    <tr>
                        <th class="mobile col-no">#</th>
                        <th class="mobile col-date">開催日</th>
                        <th class="mobile">タイトル</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($performances as $performance)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="td_date">{{ date('Y.m.d', strtotime($performance['date'])) }}</td>
                            <td class="td_title"><a href="{{ $performance['url'] }}">{{ $performance['concert']->title }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <br>
    </div>
@endsection
