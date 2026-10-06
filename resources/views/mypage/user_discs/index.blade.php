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
            <h1 class="database-title" style="text-align: center;">{{ $label }}</h1>
            <p class="database-subtitle" style="text-align: center;">{{ $artist->name }} — {{ $kind === 'albums' ? 'すべてのアルバム' : 'すべてのシングル' }}</p>
        </div>
    </div>

    <div class="container-lg database-year-content">
        @if ($discs->isEmpty())
            <p style="text-align: center; color: #999; margin-top: 40px;">
                {{ $kind === 'albums' ? 'まだアルバムが登録されていません。' : 'まだシングルが登録されていません。' }}
                @if ($isOwner)
                    <br><a href="{{ route('mypage.manage.discs', $artist->id) }}">シングル・アルバムを管理</a>
                @endif
            </p>
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
                            {{-- 番号は発売日の順で自動（種類ごと）。番号の無い EP・ベストアルバムは種類を出す --}}
                            <td>
                                @if ($kind === 'singles')
                                    {{ $disc->ep ? 'EP' : ($numbers[$disc->id] ?? '') }}
                                @elseif ($disc->best)
                                    ベストアルバム
                                @elseif ($disc->mini)
                                    ミニアルバム {{ $numbers[$disc->id] ?? '' }}
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
