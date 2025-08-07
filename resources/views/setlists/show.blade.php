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

                {{-- 通常ライブ --}}
                @if (!$setlists->fes)
                    @php $count = 0; @endphp
                    <ol class="setlist">
                        @foreach ($setlists->setlist as $data)
                            @php
                                $parts = splitAnnotation($data['song']);
                                $main = $parts['main'];
                                $annotation = $parts['annotation'];
                                $matchType = hasAnnotation($data['song']) ? 'partial' : 'exact';
                                $keyword = hasAnnotation($data['song']) ? $main : $data['song'];

                                $params = [
                                    'artist_id' => $setlists->artist_id,
                                    'keyword' => $keyword,
                                    'match_type' => $matchType,
                                ];
                                $url = url('/search') . '?' . http_build_query($params);
                            @endphp

                            @if (!empty($data['medley']) && $data['medley'] == 1)
                                - <a href="{{ $url }}">{{ $keyword }}</a>{{ $annotation }}<br>
                            @else
                                @php $count++; @endphp
                                <li>
                                    <a href="{{ $url }}">{{ $keyword }}</a>{{ $annotation }}
                                </li>
                            @endif
                        @endforeach
                    </ol>

                    {{-- アンコール --}}
                    @if (!empty($setlists->encore))
                        <hr width="250">
                        <ol class="setlist" start="{{ $count + 1 }}">
                            @foreach ((array) $setlists->encore as $data)
                                @php
                                    $parts = splitAnnotation($data['song']);
                                    $main = $parts['main'];
                                    $annotation = $parts['annotation'];
                                    $matchType = hasAnnotation($data['song']) ? 'partial' : 'exact';
                                    $keyword = hasAnnotation($data['song']) ? $main : $data['song'];

                                    $params = [
                                        'artist_id' => $setlists->artist_id,
                                        'keyword' => $keyword,
                                        'match_type' => $matchType,
                                    ];
                                    $url = url('/search') . '?' . http_build_query($params);
                                @endphp

                                @if (!empty($data['medley']) && $data['medley'] == 1)
                                    - {{ $data['song'] }}<br>
                                @else
                                    <li>
                                        <a href="{{ $url }}">{{ $keyword }}</a>{{ $annotation }}
                                    </li>
                                @endif
                            @endforeach
                        </ol>
                    @endif

                {{-- フェス形式 --}}
                @elseif($setlists->fes)
                    @php
                        $prevArtistId = null;
                        $count = 0;
                    @endphp
                    @foreach ((array) $setlists->fes_setlist as $key => $data)
                        @php
                            $artistId = isset($data['artist']) ? (is_numeric($data['artist']) ? $data['artist'] : $artistNameToId[$data['artist']] ?? null) : null;
                            $artistName = isset($data['artist']) ? $artistIdToName[$artistId] ?? $data['artist'] : '';
                            $parts = splitAnnotation($data['song']);
                            $main = $parts['main'];
                            $annotation = $parts['annotation'];
                            $matchType = hasAnnotation($data['song']) ? 'partial' : 'exact';
                            $keyword = hasAnnotation($data['song']) ? $main : $data['song'];
                            $isNewArtistGroup = $key == 0 || $artistId !== $prevArtistId;

                            $params = $artistId
                                ? ['artist_id' => $artistId, 'keyword' => $keyword, 'match_type' => $matchType]
                                : ['keyword' => $keyword, 'match_type' => $matchType];
                            $url = url('/search') . '?' . http_build_query($params);
                        @endphp

                        @if (!empty($data['artist']))
                            @if ($isNewArtistGroup)
                                @if ($key !== 0)</ol>@endif
                                <ol class="setlist">
                                    <a href="{{ url('/setlists/artists', $artistId) }}">{{ $artistName }}</a><br>
                            @endif
                            <li>
                                @php $count++; @endphp
                                <a href="{{ $url }}">{{ $keyword }}</a>{{ $annotation }}
                            </li>
                        @else
                            @if ($key == 0)
                                <ol class="setlist">
                            @endif
                            <li>
                                @php $count++; @endphp
                                <a href="{{ $url }}">{{ $keyword }}</a>{{ $annotation }}
                            </li>
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
                                $artistId = isset($data['artist']) ? (is_numeric($data['artist']) ? $data['artist'] : $artistNameToId[$data['artist']] ?? null) : null;
                                $artistName = isset($data['artist']) ? $artistIdToName[$artistId] ?? $data['artist'] : '';
                                $parts = splitAnnotation($data['song']);
                                $main = $parts['main'];
                                $annotation = $parts['annotation'];
                                $matchType = hasAnnotation($data['song']) ? 'partial' : 'exact';
                                $keyword = hasAnnotation($data['song']) ? $main : $data['song'];
                                $isNewArtistGroup = $key == 0 || $artistId !== $prevArtistId;

                                $params = $artistId
                                    ? ['artist_id' => $artistId, 'keyword' => $keyword, 'match_type' => $matchType]
                                    : ['keyword' => $keyword, 'match_type' => $matchType];
                                $url = url('/search') . '?' . http_build_query($params);
                            @endphp

                            @if (!empty($data['artist']))
                                @if ($isNewArtistGroup)
                                    @if ($key !== 0)</ol>@endif
                                    <ol class="setlist">
                                        <a href="{{ url('/setlists/artists', $artistId) }}">{{ $artistName }}</a><br>
                                @endif
                                <li><a href="{{ $url }}">{{ $keyword }}</a>{{ $annotation }}</li>
                            @else
                                @if ($key == 0)<ol class="setlist">@endif
                                <li><a href="{{ $url }}">{{ $keyword }}</a>{{ $annotation }}</li>
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