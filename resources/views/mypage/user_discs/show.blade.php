@extends('layouts.app')
@section('title', 'Yuki Official - ' . $disc->title)

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $artist->name, 'url' => route('mypage.user_artists.show', $artist->id)],
                ['label' => $kind === 'albums' ? 'Albums' : 'Singles', 'url' => route($kind === 'albums' ? 'mypage.user_artists.albums' : 'mypage.user_artists.singles', $artist->id)],
                ['label' => $disc->title],
            ]])
            <p class="database-subtitle">
                <a href="{{ route('mypage.user_artists.show', $artist->id) }}">{{ $artist->name }}</a>
            </p>
            <p class="database-subtitle">
                {{-- 公式と同じ「1st Single」「2nd Album」「1st Mini Album」。番号は発売日の順で自動 --}}
                @if ($kind === 'singles')
                    {{ $disc->ep ? 'EP' : trim(($number ? ordinal($number) : '') . ' Single') }}
                @elseif ($disc->best)
                    Best Album
                @else
                    {{ trim(($number ? ordinal($number) : '') . ($disc->mini ? ' Mini Album' : ' Album')) }}
                @endif
            </p>
            <h1 class="database-title">{{ $disc->title }}</h1>
            @if ($disc->date)
                <p class="database-subtitle">Release: {{ $disc->date->format('Y.m.d') }}</p>
            @endif
            @if ($isOwner)
                <p style="margin-top: 8px;">
                    <a href="{{ route('mypage.manage.discs.edit', [$artist->id, $kind === 'albums' ? 'album' : 'single', $disc->id]) }}" style="color: white; font-size: 0.85rem;"><i class="fa-solid fa-pen"></i> 編集</a>
                </p>
            @endif
        </div>
    </div>

    <div class="container database-year-content">
        @if ($tracksByDisc->isEmpty())
            <p style="text-align: center; color: #999;">収録曲が登録されていません。</p>
        @else
            <div style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); padding: 30px; max-width: 560px; margin: 0 auto;">
                @foreach ($tracksByDisc as $discNumber => $tracks)
                    @if ($tracksByDisc->count() > 1)
                        <h6 style="color: #999; font-size: 0.85rem; margin: {{ $loop->first ? '0' : '20px' }} 0 6px;">Disc {{ $discNumber }}</h6>
                    @endif
                    <ol style="margin: 0; padding-left: 25px; font-size: 15px; line-height: 2;">
                        @foreach ($tracks as $track)
                            @php $song = $songs[$track['id']] ?? null; @endphp
                            <li>
                                @if ($song)
                                    <a href="{{ route('mypage.user_songs.show', $song->id) }}" style="color: #667eea; text-decoration: none; font-weight: 500;">{{ $track['exception'] ?? $song->title }}</a>
                                @else
                                    <span style="color: #718096;">{{ $track['exception'] ?? '' }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endforeach
            </div>
        @endif
    </div>
@endsection
