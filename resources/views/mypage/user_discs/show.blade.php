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
            {{-- 本人には、セトリの詳細ページと同じくペンのアイコンだけの編集リンクを、発売日の右に出す --}}
            @if ($disc->date || $isOwner)
                <p class="database-subtitle">
                    @if ($disc->date)
                        Release: {{ $disc->date->format('Y.m.d') }}
                    @endif
                    @if ($isOwner)
                        <a href="{{ route('mypage.manage.discs.edit', [$artist->id, $kind === 'albums' ? 'album' : 'single', $disc->id]) }}" title="編集" style="color: white; margin-left: 6px;"><i class="fa-solid fa-pen" style="font-size: 0.75em;"></i></a>
                    @endif
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
                        {{-- 公式のアルバムの詳細と同じディスクの見出し --}}
                        <div style="font-weight: 400; color: #2d3748; margin-top: {{ $loop->first ? '0' : '15px' }}; margin-bottom: 5px;">Disc {{ $discNumber }}</div>
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

        {{-- 前後リンク（公式と同じ） --}}
        @php $showRoute = $kind === 'albums' ? 'mypage.user_albums.show' : 'mypage.user_singles.show'; @endphp
        <div style="display: flex; justify-content: space-between; max-width: 560px; margin: 40px auto 0; padding-bottom: 40px;">
            @if ($previous)
                <a href="{{ route($showRoute, $previous->id) }}" rel="prev"
                    style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                    Previous
                </a>
            @else
                <div></div>
            @endif
            @if ($next)
                <a href="{{ route($showRoute, $next->id) }}" rel="next"
                    style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    Next
                    <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            @endif
        </div>
    </div>
@endsection
