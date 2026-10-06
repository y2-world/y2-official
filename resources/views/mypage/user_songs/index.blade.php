@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' Songs')

@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $artist->name, 'url' => route('mypage.user_artists.show', $artist->id)],
                ['label' => 'Songs'],
            ]])
            @include('mypage.user_artists._discography_header', ['title' => 'Songs', 'subtitle' => 'すべての楽曲コレクション', 'current' => 'songs'])
        </div>
    </div>

    <div class="container-lg database-year-content">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mobile">#</th>
                    <th class="mobile">タイトル</th>
                    <th class="mobile">シングル / アルバム</th>
                    <th class="pc">リリース日</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($songs as $index => $song)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><a href="{{ route('mypage.user_songs.show', $song->id) }}">{{ $song->title }}</a></td>
                        {{-- 収録シングル・アルバム（公式の曲の一覧と同じく、両方あれば先に出た方） --}}
                        @php
                            $single = $singlesBySong[$song->id] ?? null;
                            $album = $albumsBySong[$song->id] ?? null;
                            $disc = $single && $album ? ($single->date > $album->date ? $album : $single) : ($single ?? $album);
                        @endphp
                        @if ($disc)
                            <td><a href="{{ $disc instanceof \App\Models\UserAlbum ? route('mypage.user_albums.show', $disc->id) : route('mypage.user_singles.show', $disc->id) }}">{{ $disc->title }}</a></td>
                            <td class="pc">{{ $disc->date?->format('Y.m.d') }}</td>
                        @else
                            <td></td>
                            <td class="pc"></td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">曲がありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
