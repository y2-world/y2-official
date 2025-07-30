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

                // []注釈だけ分離（丸括弧は注釈扱いしない）
                function splitAnnotation($song) {
                    preg_match('/^(.*?)(\s*\[[^\]]+\])?$/u', $song, $matches);
                    return [
                        'main' => trim($matches[1] ?? $song),
                        'annotation' => $matches[2] ?? '',
                    ];
                }

                // []注釈があるかどうか判定
                function hasAnnotation($song) {
                    return preg_match('/\[[^\]]+\]/u', $song);
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
                <ol class="setlist">
                    @foreach ($setlists->setlist as $data)
                        @php
                            $parts = splitAnnotation($data['song']);
                            $main = $parts['main'];
                            $annotation = $parts['annotation'];
                        @endphp

                        @if (!empty($data['medley']) && $data['medley'] == 1)
                            - {{ $data['song'] }}<br>
                        @else
                            <li>
                                @if (hasAnnotation($data['song']))
                                    {{-- []注釈あり：曲名のみリンク --}}
                                    <a href="{{ url('/search?artist_id=' . $setlists->artist_id . '&keyword=' . urlencode($main)) }}">{{ $main }}</a>{{ $annotation }}
                                @else
                                    {{-- 注釈なしは曲名全部リンク（丸括弧あっても含める） --}}
                                    <a href="{{ url('/search?artist_id=' . $setlists->artist_id . '&keyword=' . urlencode($data['song'])) }}">{{ $data['song'] }}</a>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ol>

                {{-- アンコール --}}
                @if (!empty($setlists->encore))
                    <hr width="250">
                    <ol class="setlist">
                        @foreach ((array) $setlists->encore as $data)
                            @php
                                $parts = splitAnnotation($data['song']);
                                $main = $parts['main'];
                                $annotation = $parts['annotation'];
                            @endphp

                            @if (!empty($data['medley']) && $data['medley'] == 1)
                                - {{ $data['song'] }}<br>
                            @else
                                <li>
                                    @if (hasAnnotation($data['song']))
                                        <a href="{{ url('/search?artist_id=' . $setlists->artist_id . '&keyword=' . urlencode($main)) }}">{{ $main }}</a>{{ $annotation }}
                                    @else
                                        <a href="{{ url('/search?artist_id=' . $setlists->artist_id . '&keyword=' . urlencode($data['song'])) }}">{{ $data['song'] }}</a>
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ol>
                @endif

            {{-- フェス形式 --}}
            @elseif($setlists->fes)
                @php
                    $prevArtistId = null;
                @endphp
                @foreach ((array) $setlists->fes_setlist as $key => $data)
                    @php
                        $artistId = is_numeric($data['artist'] ?? null) ? $data['artist'] : ($artistNameToId[$data['artist']] ?? null);
                        $artistName = $artistIdToName[$artistId] ?? ($data['artist'] ?? '');
                        $parts = splitAnnotation($data['song']);
                        $main = $parts['main'];
                        $annotation = $parts['annotation'];

                        $isNewArtistGroup = ($key == 0) || ($artistId !== $prevArtistId);
                    @endphp

                    @if (!empty($data['artist']))
                        @if ($isNewArtistGroup)
                            @if ($key !== 0)
                                </ol>
                            @endif
                            <ol class="setlist">
                                <a href="{{ url('/setlists/artists', $artistId) }}">{{ $artistName }}</a><br>
                        @endif
                        <li>
                            @if (hasAnnotation($data['song']))
                                <a href="{{ url('/search?artist_id=' . $artistId . '&keyword=' . urlencode($main)) }}">{{ $main }}</a>{{ $annotation }}
                            @else
                                <a href="{{ url('/search?artist_id=' . $artistId . '&keyword=' . urlencode($data['song'])) }}">{{ $data['song'] }}</a>
                            @endif
                        </li>
                    @else
                        @if ($key == 0)
                            <ol class="setlist">
                        @endif
                        <li>
                            @if (hasAnnotation($data['song']))
                                <a href="{{ url('/search?keyword=' . urlencode($main)) }}">{{ $main }}</a>{{ $annotation }}
                            @else
                                <a href="{{ url('/search?keyword=' . urlencode($data['song'])) }}">{{ $data['song'] }}</a>
                            @endif
                        </li>
                    @endif

                    @php $prevArtistId = $artistId; @endphp
                @endforeach
                </ol>

                {{-- フェスアンコール --}}
                @if (!empty($setlists->fes_encore))
                    <hr width="250">
                    @php
                        $prevArtistId = null;
                    @endphp
                    @foreach ((array) $setlists->fes_encore as $key => $data)
                        @php
                            $artistId = is_numeric($data['artist'] ?? null) ? $data['artist'] : ($artistNameToId[$data['artist']] ?? null);
                            $artistName = $artistIdToName[$artistId] ?? ($data['artist'] ?? '');
                            $parts = splitAnnotation($data['song']);
                            $main = $parts['main'];
                            $annotation = $parts['annotation'];

                            $isNewArtistGroup = ($key == 0) || ($artistId !== $prevArtistId);
                        @endphp

                        @if (!empty($data['artist']))
                            @if ($isNewArtistGroup)
                                @if ($key !== 0)
                                    </ol>
                                @endif
                                <ol class="setlist">
                                    <a href="{{ url('/setlists/artists', $artistId) }}">{{ $artistName }}</a><br>
                            @endif
                            <li>
                                @if (hasAnnotation($data['song']))
                                    <a href="{{ url('/search?artist_id=' . $artistId . '&keyword=' . urlencode($main)) }}">{{ $main }}</a>{{ $annotation }}
                                @else
                                    <a href="{{ url('/search?artist_id=' . $artistId . '&keyword=' . urlencode($data['song'])) }}">{{ $data['song'] }}</a>
                                @endif
                            </li>
                        @else
                            @if ($key == 0)
                                <ol class="setlist">
                            @endif
                            <li>
                                @if (hasAnnotation($data['song']))
                                    <a href="{{ url('/search?keyword=' . urlencode($main)) }}">{{ $main }}</a>{{ $annotation }}
                                @else
                                    <a href="{{ url('/search?keyword=' . urlencode($data['song'])) }}">{{ $data['song'] }}</a>
                                @endif
                            </li>
                        @endif

                        @php $prevArtistId = $artistId; @endphp
                    @endforeach
                    </ol>
                @endif
            @endif

            <div class="show_button">
                @if (!empty($previous))
                    <a class="btn btn-outline-dark" href="{{ route('setlists.show', $previous->id) }}" rel="prev" role="button">
                        <i class="fa-solid fa-arrow-left fa-lg"></i>
                    </a>
                @endif
                @if (!empty($next))
                    <a class="btn btn-outline-dark" href="{{ route('setlists.show', $next->id) }}" rel="next" role="button">
                        <i class="fa-solid fa-arrow-right fa-lg"></i>
                    </a>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection