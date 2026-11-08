@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' Venues')
@section('content')
    {{-- アーティストのツアー・ライブのスケジュールに出てくる会場の一覧（公演の多い順）。会場を押すと、その会場の公演の一覧 --}}
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'Database', 'url' => '/database'],
                ['label' => $artist->name, 'url' => route('database.artist', $artist->id)],
                ['label' => 'Venues'],
            ]])
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title" style="white-space: nowrap;">Venues</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">{{ $artist->name }} — すべての会場・全{{ count($venues) }}件</p>
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
        <table class="table table-striped fixed-cols">
            <thead>
                <tr>
                    <th class="mobile col-no">#</th>
                    <th class="mobile">会場</th>
                    <th class="mobile">公演数</th>
                    <th class="pc col-date">最後の公演</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($venues as $venue)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><a href="{{ route('database.venues', [$artist->id, 'keyword' => $venue->venue]) }}">{{ $venue->venue }}</a></td>
                        <td>{{ $venue->count }}</td>
                        <td class="pc">{{ date('Y.m.d', strtotime($venue->last)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">会場の情報がありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <br>
    </div>
@endsection
