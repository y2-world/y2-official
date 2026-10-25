@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' ' . $bio->year)
@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
            ['label' => 'Database', 'url' => '/database'],
            ['label' => $artist->name, 'url' => route('database.artist', $artist->id)],
            ['label' => 'Biography'],
        ]])
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title" style="white-space: nowrap;">{{ $bio->year }}</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">この年のすべての楽曲とライブ情報</p>
                </div>
                <div class="header-selects" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; max-width: 100%;">
                    {{-- 虫眼鏡アイコン（SP表示のみ） --}}
                    <button type="button" id="spSearchButtonDatabaseYear" class="sp" onclick="var form = document.getElementById('spSearchFormDatabaseYear'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                    </button>
                    <select class="year-select" name="select" onChange="location.href=value;">
                        <option value="" disabled selected>Discography</option>
                        <option value="{{ route('database.songs', $artist->id) }}">Songs</option>
                        <option value="{{ route('database.singles', $artist->id) }}">Singles</option>
                        <option value="{{ route('database.albums', $artist->id) }}">Albums</option>
                    </select>
                    <select class="year-select" name="select" onChange="location.href=value;">
                        <option value="" disabled selected>Live</option>
                        <option value="{{ route('database.live', $artist->id) }}">All</option>
                        <option value="{{ route('database.live', $artist->id) }}?type=6">Tours</option>
                        <option value="{{ route('database.live', $artist->id) }}?type=2">Events</option>
                        @if($artist->name === 'Mr.Children')
                        <option value="{{ route('database.live', $artist->id) }}?type=3">ap bank fes</option>
                        <option value="{{ route('database.live', $artist->id) }}?type=4">Solo</option>
                        @endif
                    </select>
                    <select class="year-select" name="select" onChange="location.href=value;">
                        <option value="" disabled>Years</option>
                        @foreach ($bios as $b)
                            <option value="{{ route('database.biography.year', [$artist->id, $b->year]) }}" {{ $b->year == $bio->year ? 'selected' : '' }}>{{ $b->year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0;">
                    @livewire('database-song-search', ['artistId' => $artist->id])
                </div>
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormDatabaseYear" style="margin-top: 15px; display: none;">
                @livewire('database-song-search', ['artistId' => $artist->id])
            </div>
        </div>
    </div>

    @include('database._biography_body', [
        'songs' => $songs,
        'tours' => $tours,
        'songUrl' => fn ($song) => route('songs.show', $song->id),
        'discUrl' => fn ($disc) => $disc instanceof \App\Models\DbAlbum ? route('albums.show', $disc->id) : route('singles.show', $disc->id),
        'tourUrl' => fn ($tour) => route('live.show', $tour->id),
        'songNumber' => fn ($song) => \App\Models\DbSong::where('artist_id', $song->artist_id)->where('id', '<=', $song->id)->count(),
    ])
@endsection
