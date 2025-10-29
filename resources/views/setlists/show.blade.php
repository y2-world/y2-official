@extends('layouts.app')
@section('title', 'Yuki Official - ' . $setlists->title)
@section('content')
    <br>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">

                @php
                    $artistNameToId = [];
                    $artistIdToName = [];
                    foreach ($artists as $artist) {
                        $artistNameToId[$artist['name']] = $artist['id'];
                        $artistIdToName[$artist['id']] = $artist['name'];
                    }

                    if (!function_exists('splitAnnotation')) {
                        function splitAnnotation($song) {
                            preg_match('/^(.*?)(\s*\[[^\]]+\])?$/u', $song, $matches);
                            return [
                                'main' => trim($matches[1] ?? $song),
                                'annotation' => $matches[2] ?? '',
                            ];
                        }
                    }

                    if (!function_exists('hasAnnotation')) {
                        function hasAnnotation($song) {
                            return preg_match('/\[[^\]]+\]/u', $song);
                        }
                    }
                @endphp

                <div class="setlist_artist">
                    @if (!$setlists->fes)
                        <a href="{{ url('/setlists/artists', $setlists->artist_id) }}">{{ $setlists->artist->name }}</a>
                    @endif
                </div>

                <div class="setlist_title">{{ $setlists->title }}</div>
                <div class="setlist_info">
                    {{ date('Y.m.d', strtotime($setlists->date)) }}<br>
                    <a href="{{ url('/venue?keyword=' . urlencode($setlists->venue)) }}">{{ $setlists->venue }}</a>
                </div>
                <hr>

                @php
                    // メドレー表示用関数
                    function renderSetlist($setlistItems, $artistId = null, $artistIdToName = [], $startNumber = 1) {
                        $count = 0;
                        if ($startNumber === 1) {
                            echo '<ol class="setlist">';
                        } else {
                            echo '<ol class="setlist" start="' . $startNumber . '">';
                        }
                        foreach ((array) $setlistItems as $data) {
                            $parts = splitAnnotation($data['song']);
                            $main = $parts['main'];
                            $annotation = $parts['annotation'];
                            $matchType = hasAnnotation($data['song']) ? 'partial' : 'exact';
                            $keyword = hasAnnotation($data['song']) ? $main : $data['song'];

                            $urlParams = $artistId ? ['artist_id' => $artistId, 'keyword' => $keyword, 'match_type' => $matchType]
                                                    : ['keyword' => $keyword, 'match_type' => $matchType];
                            $url = url('/search') . '?' . http_build_query($urlParams);

                            $isMedley = !empty($data['medley']) && $data['medley'] == 1;

                            if ($isMedley) {
                                echo '- <a href="' . $url . '">' . $keyword . '</a>';
                                if (!empty($annotation)) {
                                    echo ' ' . $annotation;
                                }
                                echo '<br>';
                            } else {
                                $count++;
                                echo '<li><a href="' . $url . '">' . $keyword . '</a>';
                                if (!empty($annotation)) {
                                    echo ' ' . $annotation;
                                }
                                echo '</li>';
                            }
                        }
                        echo '</ol>';
                        return $count;
                    }
                @endphp

                {{-- 通常ライブ --}}
                @if (!$setlists->fes)
                    @php $count = renderSetlist($setlists->setlist, $setlists->artist_id, [], 1); @endphp

                    {{-- アンコール --}}
                    @if (!empty($setlists->encore))
                        <hr width="250">
                        @php $count += renderSetlist($setlists->encore, $setlists->artist_id, [], $count + 1); @endphp
                    @endif

                {{-- フェス形式 --}}
                @elseif($setlists->fes)
                    @php
                        $prevArtistId = null;
                    @endphp

                    {{-- フェス本編 --}}
                    @foreach ((array) $setlists->fes_setlist as $key => $data)
                        @php
                            $artistId = isset($data['artist']) 
                                ? (is_numeric($data['artist']) ? $data['artist'] : $artistNameToId[$data['artist']] ?? null) 
                                : null;
                            $artistName = $artistIdToName[$artistId] ?? ($data['artist'] ?? '');
                        @endphp

                        @if ($key == 0 || $artistId !== $prevArtistId)
                            @if ($key != 0) </ol> @endif
                            <ol class="setlist">
                                @if ($artistName)
                                    <a href="{{ url('/setlists/artists', $artistId) }}">{{ $artistName }}</a><br>
                                @endif
                        @endif

                        @php
                            $isMedley = !empty($data['medley']) && $data['medley'] == 1;
                            $parts = splitAnnotation($data['song']);
                            $main = $parts['main'];
                            $annotation = $parts['annotation'];
                            $matchType = hasAnnotation($data['song']) ? 'partial' : 'exact';
                            $keyword = hasAnnotation($data['song']) ? $main : $data['song'];
                            $urlParams = $artistId ? ['artist_id' => $artistId, 'keyword' => $keyword, 'match_type' => $matchType]
                                                    : ['keyword' => $keyword, 'match_type' => $matchType];
                            $url = url('/search') . '?' . http_build_query($urlParams);
                        @endphp

                        @if ($isMedley)
                            - <a href="{{ $url }}">{{ $keyword }}</a>@if(!empty($annotation)) {{ $annotation }}@endif<br>
                        @else
                            <li><a href="{{ $url }}">{{ $keyword }}</a>@if(!empty($annotation)) {{ $annotation }}@endif</li>
                        @endif

                        @php $prevArtistId = $artistId; @endphp
                    @endforeach
                    </ol>

                    {{-- フェスアンコール --}}
                    @if (!empty($setlists->fes_encore))
                        <hr width="250">
                        @php $prevArtistId = null; @endphp
                        @foreach ((array) $setlists->fes_encore as $key => $data)
                            @php
                                $artistId = isset($data['artist'])
                                    ? (is_numeric($data['artist']) ? $data['artist'] : $artistNameToId[$data['artist']] ?? null)
                                    : null;
                                $artistName = $artistIdToName[$artistId] ?? ($data['artist'] ?? '');
                                $isMedley = !empty($data['medley']) && $data['medley'] == 1;
                                $parts = splitAnnotation($data['song']);
                                $main = $parts['main'];
                                $annotation = $parts['annotation'];
                                $matchType = hasAnnotation($data['song']) ? 'partial' : 'exact';
                                $keyword = hasAnnotation($data['song']) ? $main : $data['song'];
                                $urlParams = $artistId ? ['artist_id' => $artistId, 'keyword' => $keyword, 'match_type' => $matchType]
                                                        : ['keyword' => $keyword, 'match_type' => $matchType];
                                $url = url('/search') . '?' . http_build_query($urlParams);
                            @endphp

                            @if ($key == 0 || $artistId !== $prevArtistId)
                                @if ($key != 0) </ol> @endif
                                <ol class="setlist">
                                    @if ($artistName)
                                        <a href="{{ url('/setlists/artists', $artistId) }}">{{ $artistName }}</a><br>
                                    @endif
                            @endif

                            @if ($isMedley)
                                - <a href="{{ $url }}">{{ $keyword }}</a>@if(!empty($annotation)) {{ $annotation }}@endif<br>
                            @else
                                <li><a href="{{ $url }}">{{ $keyword }}</a>@if(!empty($annotation)) {{ $annotation }}@endif</li>
                            @endif

                            @php $prevArtistId = $artistId; @endphp
                        @endforeach
                        </ol>
                    @endif
                @endif

                {{-- 前後リンク --}}
                <div class="show_button">
                    @if (!empty($previous))
                        <a class="btn btn-outline-dark" href="{{ route('setlists.show', $previous->id) }}" rel="prev">
                            <i class="fa-solid fa-arrow-left fa-lg"></i>
                        </a>
                    @endif
                    @if (!empty($next))
                        <a class="btn btn-outline-dark" href="{{ route('setlists.show', $next->id) }}" rel="next">
                            <i class="fa-solid fa-arrow-right fa-lg"></i>
                        </a>
                    @endif
                </div>

            </div>
        </div>
    </div>
@endsection