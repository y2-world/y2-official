@extends('layouts.app')
@section('title', 'Yuki Official - ' . $albums->title)
@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
            ['label' => 'Database', 'url' => '/database'],
            ['label' => $artist->name, 'url' => route('database.artist', $artist->id)],
            ['label' => 'Albums', 'url' => route('database.albums', $artist->id)],
            ['label' => $albums->title],
        ]])
            <p class="database-subtitle" style="">
                @if ($albums->best)
                    Best Album
                @elseif ($albums->mini && $albums->album_id)
                    {{ ordinal($albums->album_id) }} Mini Album
                @elseif ($albums->mini)
                    Mini Album
                @elseif ($albums->album_id)
                    {{ ordinal($albums->album_id) }} Album
                @endif
            </p>
            <h1 class="database-title" style="">{{ $albums->title }}</h1>
            <p class="database-subtitle" style="">Release: {{ date('Y.m.d', strtotime($albums->date)) }}</p>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormAlbum" style="margin-top: 10px; display: none;">
                @livewire('database-song-search', ['artistId' => $artist->id])
                <div style="text-align: center; margin-top: 15px;">
                    <button type="button" onclick="document.getElementById('spSearchFormAlbum').style.display='none';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                    </button>
                </div>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc song-search-top-right">
                @livewire('database-song-search', ['artistId' => $artist->id])
            </div>
        </div>
    </div>

    <div class="container database-year-content">
        @if (isset($albums->tracklist))
            <div
                style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); padding: 30px; max-width: 560px; margin: 0 auto;">
                @php
                    $previousDisc = null;
                @endphp

                @foreach ($albums->tracklist as $data)
                    @php
                        $currentDisc = $data['disc'] ?? null;
                    @endphp

                    {{-- ディスク番号が変わった場合は ol をリセット --}}
                    @if ($currentDisc && $currentDisc !== $previousDisc)
                        @if (!$loop->first)
                            </ol> {{-- 前の ol を閉じる --}}
                        @endif
                        <div style="font-weight: 600; color: #2d3748; margin-top: 15px; margin-bottom: 5px;">
                            {{ $currentDisc }}
                        </div>
                        <ol style="margin: 0; padding-left: 25px; font-size: 15px; line-height: 2;">
                        @elseif($loop->first)
                            <ol style="margin: 0; padding-left: 25px; font-size: 15px; line-height: 2;">
                    @endif

                    <li>
                        @if (isset($data['id']))
                            <a href="{{ url('/database/songs', $data['id']) }}"
                                style="color: #667eea; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">
                                {{ $data['exception'] ?? ($songs[$data['id']]->title ?? '') }}
                            </a>
                        @else
                            <span style="color: #718096;">{{ $data['exception'] }}</span>
                        @endif
                    </li>

                    @php $previousDisc = $currentDisc; @endphp

                    @if ($loop->last)
                        </ol> {{-- 最後の ol を閉じる --}}
                    @endif
                @endforeach
        @endif

        {{-- 前後リンク --}}
        <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
            @if (isset($previous))
                <a href="{{ route('albums.show', $previous->id) }}" rel="prev"
                    style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                    Previous
                </a>
            @else
                <div></div>
            @endif
            @if (isset($next))
                <a href="{{ route('albums.show', $next->id) }}" rel="next"
                    style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    Next
                    <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            @endif
        </div>
    </div>
@endsection
