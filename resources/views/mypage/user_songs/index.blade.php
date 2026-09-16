@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' Songs')

@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $artist->name, 'url' => route('mypage.user_artists.live', $artist->id)],
                ['label' => 'Songs'],
            ]])
            <h1 class="database-title" style="text-align: center;">Songs</h1>
            <p class="database-subtitle" style="text-align: center;">{{ $artist->name }} — すべての楽曲</p>
        </div>
    </div>

    <div class="container database-year-content">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mobile">#</th>
                    <th class="mobile">タイトル</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($songs as $index => $song)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><a href="{{ route('mypage.user_songs.show', $song->id) }}">{{ $song->title }}</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center">曲がありません</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
