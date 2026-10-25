@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' ' . $year)
{{-- マイページのアーティストの Biography の年のページ（公式の database/show と同じ形。中身は共通の database/_biography_body） --}}
@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $artist->name, 'url' => route('mypage.user_artists.show', $artist->id)],
                ['label' => 'Biography'],
            ]])
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title" style="white-space: nowrap;">{{ $year }}</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">この年のすべての楽曲とライブ情報</p>
                </div>
                <div class="header-selects" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; max-width: 100%;">
                    <select class="year-select" name="select" onChange="location.href=value;">
                        <option value="" disabled selected>Discography</option>
                        <option value="{{ route('mypage.user_artists.songs', $artist->id) }}">Songs</option>
                        <option value="{{ route('mypage.user_artists.singles', $artist->id) }}">Singles</option>
                        <option value="{{ route('mypage.user_artists.albums', $artist->id) }}">Albums</option>
                    </select>
                    <select class="year-select" name="select" onChange="location.href=value;">
                        <option value="" disabled selected>Live</option>
                        <option value="{{ route('mypage.user_artists.live', $artist->id) }}">All</option>
                        <option value="{{ route('mypage.user_artists.live', [$artist->id, 'type' => 6]) }}">Tours</option>
                        <option value="{{ route('mypage.user_artists.live', [$artist->id, 'type' => 2]) }}">Events</option>
                    </select>
                    @include('mypage.user_artists._years_select', ['selectedYear' => $year])
                </div>
            </div>
        </div>
    </div>

    @include('database._biography_body', [
        'songs' => $songs,
        'tours' => $tours,
        'songUrl' => fn ($song) => route('mypage.user_songs.show', $song->id),
        'discUrl' => fn ($disc) => $disc instanceof \App\Models\UserAlbum ? route('mypage.user_albums.show', $disc->id) : route('mypage.user_singles.show', $disc->id),
        'tourUrl' => fn ($tour) => route('mypage.user_concerts.show', $tour->id),
        // 曲番は曲のページと同じ（アーティストの曲の並び順で何番目か）
        'songNumber' => fn ($song) => \App\Models\UserSong::where('user_artist_id', $song->user_artist_id)->where('sort_order', '<=', $song->sort_order)->count(),
    ])
@endsection
