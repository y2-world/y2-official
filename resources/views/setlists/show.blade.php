@extends('layouts.app')
@section('title', 'Yuki Official - ' . $setlists->title)
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            @if (!$setlists->fes && $setlists->artist)
                <p class="database-subtitle" style="margin-bottom: 10px;">
                    <a href="{{ url('/setlists/artists', $setlists->artist_id) }}" style="color: white; text-decoration: none;">
                        {{ $setlists->artist->name }}
                    </a>
                </p>
            @endif
            <h1 class="database-title" style="margin-bottom: 15px;">{{ $setlists->title }}</h1>
            <p class="database-subtitle" style="margin-bottom: 0;">
                {{ date('Y.m.d', strtotime($setlists->date)) }}<br>
                <a href="{{ url('/venue?keyword=' . urlencode($setlists->venue)) }}" style="color: white; text-decoration: none;">
                    {{ $setlists->venue }}
                </a>
            </p>
        </div>
    </div>

    <div class="container database-year-content">
        <div class="row justify-content-center">
            <div class="col-xl-9">

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

                @php
                    // メドレー表示用関数
                    function renderSetlist($setlistItems, $artistId = null, $artistIdToName = [], $startNumber = 1, $closeOl = true) {
                        $count = 0;
                        if ($startNumber === 1) {
                            echo '<ol class="setlist">';
                        } else {
                            echo '<ol class="setlist" start="' . $startNumber . '">';
                        }
                        foreach ((array) $setlistItems as $data) {
                            // songフィールドの処理：数値ならSetlistSongのID、文字列なら直接曲名
                            $songValue = $data['song'] ?? '';
                            $isNumericId = is_numeric($songValue);

                            if ($isNumericId) {
                                // SetlistSongテーブルから曲名を取得
                                $song = \App\Models\SetlistSong::find($songValue);
                                $songTitle = $song ? $song->title : $songValue;
                                // SetlistSongの詳細ページにリンク
                                $url = url('/setlist-songs/' . $songValue);
                            } else {
                                // 文字列の場合はSetlistSongテーブルから検索
                                $songTitle = $songValue;
                                // タイトルから[xxx]の部分を削除して検索
                                $cleanTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $songTitle);
                                $cleanTitle = trim($cleanTitle);

                                // SetlistSongを検索（タイトルとartist_idで）
                                $setlistSong = \App\Models\SetlistSong::where('title', $cleanTitle);
                                if ($artistId) {
                                    $setlistSong = $setlistSong->where('artist_id', $artistId);
                                }
                                $setlistSong = $setlistSong->first();

                                if ($setlistSong) {
                                    // 見つかった場合は詳細ページにリンク
                                    $url = url('/setlist-songs/' . $setlistSong->id);
                                } else {
                                    // 見つからない場合はテキストのみ（リンクなし）
                                    $url = '#';
                                }
                            }

                            $parts = splitAnnotation($songTitle);
                            $main = $parts['main'];
                            $annotation = $parts['annotation'];
                            $keyword = $main;

                            // 共演者がある場合は曲名の後に追加
                            $featuring = !empty($data['featuring']) ? ' / ' . $data['featuring'] : '';

                            // バージョン違いがある場合は共演者の後に追加
                            $version = !empty($data['version']) ? ' ' . $data['version'] : '';

                            $isMedley = !empty($data['medley']) && $data['medley'] == 1;

                            if ($isMedley) {
                                if ($url !== '#') {
                                    echo '- <a href="' . $url . '">' . $keyword . '</a>';
                                } else {
                                    echo '- ' . $keyword;
                                }
                                if (!empty($featuring)) {
                                    echo $featuring;
                                }
                                if (!empty($version)) {
                                    echo $version;
                                }
                                if (!empty($annotation)) {
                                    echo ' ' . $annotation;
                                }
                                echo '<br>';
                            } else {
                                $count++;
                                echo '<li>';
                                if ($url !== '#') {
                                    echo '<a href="' . $url . '">' . $keyword . '</a>';
                                } else {
                                    echo $keyword;
                                }
                                if (!empty($featuring)) {
                                    echo $featuring;
                                }
                                if (!empty($version)) {
                                    echo $version;
                                }
                                if (!empty($annotation)) {
                                    echo ' ' . $annotation;
                                }
                                echo '</li>';
                            }
                        }
                        if ($closeOl) {
                            echo '</ol>';
                        }
                        return $count;
                    }

                    // アンコールのみをレンダリングする関数（<ol>タグを開かない）
                    function renderEncoreSetlist($setlistItems, $artistId = null) {
                        $count = 0;
                        foreach ((array) $setlistItems as $data) {
                            // songフィールドの処理：数値ならSetlistSongのID、文字列なら直接曲名
                            $songValue = $data['song'] ?? '';
                            $isNumericId = is_numeric($songValue);

                            if ($isNumericId) {
                                // SetlistSongテーブルから曲名を取得
                                $song = \App\Models\SetlistSong::find($songValue);
                                $songTitle = $song ? $song->title : $songValue;
                                // SetlistSongの詳細ページにリンク
                                $url = url('/setlist-songs/' . $songValue);
                            } else {
                                // 文字列の場合はSetlistSongテーブルから検索
                                $songTitle = $songValue;
                                // タイトルから[xxx]の部分を削除して検索
                                $cleanTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $songTitle);
                                $cleanTitle = trim($cleanTitle);

                                // SetlistSongを検索（タイトルとartist_idで）
                                $setlistSong = \App\Models\SetlistSong::where('title', $cleanTitle);
                                if ($artistId) {
                                    $setlistSong = $setlistSong->where('artist_id', $artistId);
                                }
                                $setlistSong = $setlistSong->first();

                                if ($setlistSong) {
                                    // 見つかった場合は詳細ページにリンク
                                    $url = url('/setlist-songs/' . $setlistSong->id);
                                } else {
                                    // 見つからない場合はテキストのみ（リンクなし）
                                    $url = '#';
                                }
                            }

                            $parts = splitAnnotation($songTitle);
                            $main = $parts['main'];
                            $annotation = $parts['annotation'];
                            $keyword = $main;

                            // 共演者がある場合は曲名の後に追加
                            $featuring = !empty($data['featuring']) ? ' / ' . $data['featuring'] : '';

                            // バージョン違いがある場合は共演者の後に追加
                            $version = !empty($data['version']) ? ' ' . $data['version'] : '';

                            $isMedley = !empty($data['medley']) && $data['medley'] == 1;

                            if ($isMedley) {
                                if ($url !== '#') {
                                    echo '- <a href="' . $url . '">' . $keyword . '</a>';
                                } else {
                                    echo '- ' . $keyword;
                                }
                                if (!empty($featuring)) {
                                    echo $featuring;
                                }
                                if (!empty($version)) {
                                    echo $version;
                                }
                                if (!empty($annotation)) {
                                    echo ' ' . $annotation;
                                }
                                echo '<br>';
                            } else {
                                $count++;
                                echo '<li>';
                                if ($url !== '#') {
                                    echo '<a href="' . $url . '">' . $keyword . '</a>';
                                } else {
                                    echo $keyword;
                                }
                                if (!empty($featuring)) {
                                    echo $featuring;
                                }
                                if (!empty($version)) {
                                    echo $version;
                                }
                                if (!empty($annotation)) {
                                    echo ' ' . $annotation;
                                }
                                echo '</li>';
                            }
                        }
                        return $count;
                    }
                @endphp

                {{-- 通常ライブ --}}
                @if (!$setlists->fes)
                    @php $count = renderSetlist($setlists->setlist, $setlists->artist_id, [], 1, empty($setlists->encore)); @endphp

                    {{-- アンコール --}}
                    @if (!empty($setlists->encore))
                        <br>
                        <div style="margin: 0;">
                            <span style="color: #999; font-weight: 600; font-size: 0.9rem; letter-spacing: 2px;">ENCORE</span>
                        </div>
                        @php
                            $count += renderEncoreSetlist($setlists->encore, $setlists->artist_id);
                            echo '</ol>';
                        @endphp
                    @endif
                @endif

                {{-- フェス形式 --}}
                @if($setlists->fes)
                    @php
                        $prevArtistId = null;
                        $songNumber = 0; // ナンバリング用カウンター
                    @endphp

                    {{-- フェス本編 --}}
                    <ol class="setlist">
                    @foreach ((array) $setlists->fes_setlist as $key => $data)
                        @php
                            // アーティストIDを取得（数値IDまたは名前から変換）
                            $artistId = null;
                            if (isset($data['artist'])) {
                                if (is_numeric($data['artist'])) {
                                    $artistId = (int)$data['artist'];
                                } else {
                                    $artistId = $artistNameToId[$data['artist']] ?? null;
                                }
                            }
                            
                            $artistName = $artistIdToName[$artistId] ?? ($data['artist'] ?? '');
                            
                            // 桜井ソロ apの時は櫻井に変換
                            if (stripos($setlists->title, 'ap') !== false && stripos($artistName, '桜井') !== false) {
                                $artistName = str_ireplace('桜井', '櫻井', $artistName);
                            }
                            
                            // 型を統一して比較
                            $currentArtistId = $artistId ? (is_numeric($artistId) ? (int)$artistId : $artistId) : null;
                            $prevArtistIdInt = $prevArtistId ? (is_numeric($prevArtistId) ? (int)$prevArtistId : $prevArtistId) : null;
                        @endphp

                        @php
                            // songフィールドの処理：数値ならSetlistSongのID、文字列なら直接曲名
                            $songValue = $data['song'] ?? '';
                            $isNumericId = is_numeric($songValue);

                            if ($isNumericId) {
                                // SetlistSongテーブルから曲名を取得
                                $song = \App\Models\SetlistSong::find($songValue);
                                $songTitle = $song ? $song->title : $songValue;
                                // SetlistSongの詳細ページにリンク
                                $url = url('/setlist-songs/' . $songValue);
                            } else {
                                // 文字列の場合はSetlistSongテーブルから検索
                                $songTitle = $songValue;
                                // タイトルから[xxx]の部分を削除して検索
                                $cleanTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $songTitle);
                                $cleanTitle = trim($cleanTitle);
                                
                                // SetlistSongを検索（タイトルとartist_idで）
                                $setlistSong = \App\Models\SetlistSong::where('title', $cleanTitle);
                                if ($artistId) {
                                    $setlistSong = $setlistSong->where('artist_id', $artistId);
                                }
                                $setlistSong = $setlistSong->first();
                                
                                if ($setlistSong) {
                                    // 見つかった場合は詳細ページにリンク
                                    $url = url('/setlist-songs/' . $setlistSong->id);
                                } else {
                                    // 見つからない場合はテキストのみ（リンクなし）
                                    $url = '#';
                                }
                            }

                            $isMedley = !empty($data['medley']) && $data['medley'] == 1;
                            
                            // メドレー以外の曲のみナンバリング
                            if (!$isMedley) {
                                $songNumber++;
                            }
                            $parts = splitAnnotation($songTitle);
                            $main = $parts['main'];
                            $annotation = $parts['annotation'];
                            $keyword = $main;

                            // アーティスト名の表示（リンク付き）
                            $artistDisplay = '';
                            if ($artistName && $artistId) {
                                $artistDisplay = ' / <a href="' . url('/setlists/artists', $artistId) . '">' . htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8') . '</a>';
                            } elseif ($artistName) {
                                $artistDisplay = ' / ' . htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8');
                            }

                            // 共演者がある場合はアーティスト名の後に追加
                            // アーティスト名がない場合はスラッシュを付けて共演者を表示
                            if (!empty($data['featuring'])) {
                                if (empty($artistDisplay)) {
                                    $artistDisplay = ' / ' . htmlspecialchars($data['featuring'], ENT_QUOTES, 'UTF-8');
                                } else {
                                    $artistDisplay .= ' ' . htmlspecialchars($data['featuring'], ENT_QUOTES, 'UTF-8');
                                }
                            }
                        @endphp

                        @if ($isMedley)
                            <li>@if($url !== '#')<a href="{{ $url }}">{{ $keyword }}</a>@else{{ $keyword }}@endif{!! $artistDisplay !!}{{ !empty($annotation) ? ' ' . $annotation : '' }}</li>
                        @else
                            <li>@if($url !== '#')<a href="{{ $url }}">{{ $keyword }}</a>@else{{ $keyword }}@endif{!! $artistDisplay !!}{{ !empty($annotation) ? ' ' . $annotation : '' }}</li>
                        @endif

                        @php $prevArtistId = $currentArtistId; @endphp
                    @endforeach

                    {{-- フェスアンコール --}}
                    @if (!empty($setlists->fes_encore))
                        <br>
                        <div style="margin: 0;">
                            <span style="color: #999; font-weight: 600; font-size: 0.9rem; letter-spacing: 2px;">ENCORE</span>
                        </div>
                        @foreach ((array) $setlists->fes_encore as $key => $data)
                            @php
                                // アーティストIDを取得（数値IDまたは名前から変換）
                                $artistId = null;
                                if (isset($data['artist'])) {
                                    if (is_numeric($data['artist'])) {
                                        $artistId = (int)$data['artist'];
                                    } else {
                                        $artistId = $artistNameToId[$data['artist']] ?? null;
                                    }
                                }
                                
                                $artistName = $artistIdToName[$artistId] ?? ($data['artist'] ?? '');
                                
                                // 桜井ソロ apの時は櫻井に変換
                                if (stripos($setlists->title, 'ap') !== false && stripos($artistName, '桜井') !== false) {
                                    $artistName = str_ireplace('桜井', '櫻井', $artistName);
                                }
                                
                                // 型を統一して比較
                                $currentEncoreArtistId = $artistId ? (is_numeric($artistId) ? (int)$artistId : $artistId) : null;
                                $prevEncoreArtistIdInt = $prevArtistId ? (is_numeric($prevArtistId) ? (int)$prevArtistId : $prevArtistId) : null;

                                // songフィールドの処理：数値ならSetlistSongのID、文字列なら直接曲名
                                $songValue = $data['song'] ?? '';
                                $isNumericId = is_numeric($songValue);

                                if ($isNumericId) {
                                    // SetlistSongテーブルから曲名を取得
                                    $song = \App\Models\SetlistSong::find($songValue);
                                    $songTitle = $song ? $song->title : $songValue;
                                    // SetlistSongの詳細ページにリンク
                                    $url = url('/setlist-songs/' . $songValue);
                                } else {
                                    // 文字列の場合はSetlistSongテーブルから検索
                                    $songTitle = $songValue;
                                    // タイトルから[xxx]の部分を削除して検索
                                    $cleanTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $songTitle);
                                    $cleanTitle = trim($cleanTitle);
                                    
                                    // SetlistSongを検索（タイトルとartist_idで）
                                    $setlistSong = \App\Models\SetlistSong::where('title', $cleanTitle);
                                    if ($artistId) {
                                        $setlistSong = $setlistSong->where('artist_id', $artistId);
                                    }
                                    $setlistSong = $setlistSong->first();
                                    
                                    if ($setlistSong) {
                                        // 見つかった場合は詳細ページにリンク
                                        $url = url('/setlist-songs/' . $setlistSong->id);
                                    } else {
                                        // 見つからない場合はテキストのみ（リンクなし）
                                        $url = '#';
                                    }
                                }

                                $isMedley = !empty($data['medley']) && $data['medley'] == 1;
                                
                                // メドレー以外の曲のみナンバリング
                                if (!$isMedley) {
                                    $songNumber++;
                                }
                                
                                $parts = splitAnnotation($songTitle);
                                $main = $parts['main'];
                                $annotation = $parts['annotation'];
                                $keyword = $main;

                            @endphp

                            @php

                                // アーティスト名の表示（リンク付き）
                                $encoreArtistDisplay = '';
                                if ($artistName && $artistId) {
                                    $encoreArtistDisplay = ' / <a href="' . url('/setlists/artists', $artistId) . '">' . htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8') . '</a>';
                                } elseif ($artistName) {
                                    $encoreArtistDisplay = ' / ' . htmlspecialchars($artistName, ENT_QUOTES, 'UTF-8');
                                }

                                // 共演者がある場合はアーティスト名の後に追加
                                // アーティスト名がない場合はスラッシュを付けて共演者を表示
                                if (!empty($data['featuring'])) {
                                    if (empty($encoreArtistDisplay)) {
                                        $encoreArtistDisplay = ' / ' . htmlspecialchars($data['featuring'], ENT_QUOTES, 'UTF-8');
                                    } else {
                                        $encoreArtistDisplay .= ' ' . htmlspecialchars($data['featuring'], ENT_QUOTES, 'UTF-8');
                                    }
                                }
                            @endphp

                            @if ($isMedley)
                                <li>@if($url !== '#')<a href="{{ $url }}">{{ $keyword }}</a>@else{{ $keyword }}@endif{!! $encoreArtistDisplay !!}{{ !empty($annotation) ? ' ' . $annotation : '' }}</li>
                            @else
                                <li>@if($url !== '#')<a href="{{ $url }}">{{ $keyword }}</a>@else{{ $keyword }}@endif{!! $encoreArtistDisplay !!}{{ !empty($annotation) ? ' ' . $annotation : '' }}</li>
                            @endif

                            @php $prevArtistId = $currentEncoreArtistId; @endphp
                        @endforeach
                    @endif
                    </ol>
                @endif

                {{-- 前後リンク --}}
                <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                    @if (!empty($previous))
                        <a href="{{ route('setlists.show', $previous->id) }}" rel="prev"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                            Previous
                        </a>
                    @else
                        <div></div>
                    @endif
                    @if (!empty($next))
                        <a href="{{ route('setlists.show', $next->id) }}" rel="next"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            Next
                            <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection