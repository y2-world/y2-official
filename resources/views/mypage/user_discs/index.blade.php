@extends('layouts.app')
@php $label = $kind === 'albums' ? 'Albums' : 'Singles'; @endphp
@section('title', 'Yuki Official - ' . $artist->name . ' ' . $label)

@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $artist->name, 'url' => route('mypage.user_artists.show', $artist->id)],
                ['label' => $label],
            ]])
            @include('mypage.user_artists._discography_header', ['title' => $label, 'subtitle' => $kind === 'albums' ? 'すべてのアルバムコレクション' : 'すべてのシングルコレクション', 'current' => $kind])
        </div>
    </div>

    <div class="container-lg database-year-content">
        @if ($discs->isEmpty())
            <p style="text-align: center; color: #999; margin-top: 40px;">
                {{ $kind === 'albums' ? 'まだアルバムが登録されていません。' : 'まだシングルが登録されていません。' }}
            </p>
            {{-- 作った本人には、登録するためのボタンを出す --}}
            @if ($isOwner)
                <div style="text-align: center; margin-top: 16px;">
                    <a href="{{ route('mypage.manage.discs', [$artist->id, $kind === 'albums' ? 'album' : 'single']) }}" class="btn-pill">{{ $kind === 'albums' ? 'アルバムを管理' : 'シングルを管理' }}</a>
                </div>
            @endif
        @else
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="mobile">#</th>
                        <th class="mobile">タイトル</th>
                        <th class="mobile">リリース日</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($discs as $disc)
                        <tr>
                            {{-- 番号は発売日の順で自動（種類ごと）。公式と同じく、EP は「EP」、ミニアルバム・ベストアルバムは空 --}}
                            <td>
                                @if ($kind === 'singles')
                                    {{ $disc->ep ? 'EP' : ($numbers[$disc->id] ?? '') }}
                                @elseif ($disc->best || $disc->mini)
                                    {{-- 公式と同じく、ミニアルバム・ベストアルバムには番号を出さない --}}
                                @else
                                    {{ $numbers[$disc->id] ?? '' }}
                                @endif
                            </td>
                            <td><a href="{{ route($kind === 'albums' ? 'mypage.user_albums.show' : 'mypage.user_singles.show', $disc->id) }}">{{ $disc->title }}</a></td>
                            <td>{{ $disc->date?->format('Y.m.d') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
