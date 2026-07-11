@extends('layouts.app')
@section('title', 'Yuki Official - ' . $setlists->title)

@section('og_title', $setlists->title)
@section('og_description', date('Y.m.d', strtotime($setlists->date)) . ' - ' . $setlists->venue)
@section('og_type', 'article')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @if (!$setlists->fes && $setlists->artist)
                <p class="database-subtitle" style="">
                    <a href="{{ url('/setlists/artists', $setlists->artist_id) }}" style="color: white; text-decoration: none;">
                        {{ $setlists->artist->name }}
                    </a>
                </p>
            @endif
            <h1 class="database-title" style="">{{ $setlists->title }}</h1>
            <p class="database-subtitle" style="">
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
                                $song = \App\Models\SlSong::find($songValue);
                                $songTitle = $song ? $song->title : $songValue;
                                // SetlistSongの詳細ページにリンク
                                $url = url('/setlists/songs/' . $songValue);
                            } else {
                                // 文字列の場合はSetlistSongテーブルから検索
                                $songTitle = $songValue;
                                // タイトルから[xxx]の部分を削除して検索
                                $cleanTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $songTitle);
                                $cleanTitle = trim($cleanTitle);

                                // SetlistSongを検索（タイトルとartist_idで）
                                $setlistSong = \App\Models\SlSong::where('title', $cleanTitle);
                                if ($artistId) {
                                    $setlistSong = $setlistSong->where('artist_id', $artistId);
                                }
                                $setlistSong = $setlistSong->first();

                                if ($setlistSong) {
                                    // 見つかった場合は詳細ページにリンク
                                    $url = url('/setlists/songs/' . $setlistSong->id);
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
                            $featuring = !empty($data['featuring']) ? ' <span style="color:#999;font-size:0.75em;">' . $data['featuring'] . '</span>' : '';

                            // バージョン違いがある場合は共演者の後に追加
                            $version = !empty($data['version']) ? ' <span style="color:#999;font-size:0.75em;">' . $data['version'] . '</span>' : '';

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
                                $song = \App\Models\SlSong::find($songValue);
                                $songTitle = $song ? $song->title : $songValue;
                                // SetlistSongの詳細ページにリンク
                                $url = url('/setlists/songs/' . $songValue);
                            } else {
                                // 文字列の場合はSetlistSongテーブルから検索
                                $songTitle = $songValue;
                                // タイトルから[xxx]の部分を削除して検索
                                $cleanTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $songTitle);
                                $cleanTitle = trim($cleanTitle);

                                // SetlistSongを検索（タイトルとartist_idで）
                                $setlistSong = \App\Models\SlSong::where('title', $cleanTitle);
                                if ($artistId) {
                                    $setlistSong = $setlistSong->where('artist_id', $artistId);
                                }
                                $setlistSong = $setlistSong->first();

                                if ($setlistSong) {
                                    // 見つかった場合は詳細ページにリンク
                                    $url = url('/setlists/songs/' . $setlistSong->id);
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
                            $featuring = !empty($data['featuring']) ? ' <span style="color:#999;font-size:0.75em;">' . $data['featuring'] . '</span>' : '';

                            // バージョン違いがある場合は共演者の後に追加
                            $version = !empty($data['version']) ? ' <span style="color:#999;font-size:0.75em;">' . $data['version'] . '</span>' : '';

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

                <div class="setlist-row">
                <div class="live-column">

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

                {{-- フェス（type:'block'と'song'の混合対応） --}}
                @if(true)
                    @php
                        // fes_setlistをレンダリング（type:'block'と'song'の混合対応）
                        function renderFesMixed($items, $artistIdToName, $setlistsTitle) {
                            $inSongList = false;
                            foreach ((array) $items as $data) {
                                $type = $data['type'] ?? 'song';
                                if ($type === 'block') {
                                    // 開いているolを閉じる
                                    if ($inSongList) { echo '</ol>'; $inSongList = false; }
                                    $blockArtistId = isset($data['artist']) ? (int)$data['artist'] : null;
                                    $blockArtistName = $artistIdToName[$blockArtistId] ?? '';
                                    if (stripos($setlistsTitle, 'ap') !== false && stripos($blockArtistName, '桜井') !== false) {
                                        $blockArtistName = str_ireplace('桜井', '櫻井', $blockArtistName);
                                    }
                                    echo '<div class="fes-block">';
                                    if ($blockArtistName) {
                                        if ($blockArtistId) {
                                            echo '<p class="fes-block-artist"><a href="' . url('/setlists/artists', $blockArtistId) . '" style="color:inherit;text-decoration:none;">' . htmlspecialchars($blockArtistName, ENT_COMPAT, 'UTF-8') . '</a></p>';
                                        } else {
                                            echo '<p class="fes-block-artist">' . htmlspecialchars($blockArtistName, ENT_COMPAT, 'UTF-8') . '</p>';
                                        }
                                    }
                                    echo '<ol class="setlist">';
                                    foreach ((array)($data['songs'] ?? []) as $song) {
                                        $sv = $song['song'] ?? '';
                                        if (is_numeric($sv)) {
                                            $sm = \App\Models\SlSong::find($sv);
                                            $title = $sm ? $sm->title : $sv;
                                            $url = url('/setlists/songs/' . $sv);
                                        } else {
                                            $title = $sv;
                                            $clean = trim(preg_replace('/\s*\[[^\]]+\]/u', '', $title));
                                            $sm = \App\Models\SlSong::where('title', $clean)->when($blockArtistId, fn($q) => $q->where('artist_id', $blockArtistId))->first();
                                            $url = $sm ? url('/setlists/songs/' . $sm->id) : '#';
                                        }
                                        $parts = splitAnnotation($title);
                                        $keyword = $parts['main'];
                                        $annotation = $parts['annotation'];
                                        $feat = !empty($song['featuring']) ? ' <span style="color:#999;font-size:0.75em;">' . htmlspecialchars($song['featuring'], ENT_COMPAT, 'UTF-8') . '</span>' : '';
                                        $ver = !empty($song['version']) ? ' <span style="color:#999;font-size:0.75em;">' . htmlspecialchars($song['version'], ENT_COMPAT, 'UTF-8') . '</span>' : '';
                                        $isMedley = !empty($song['medley']) && $song['medley'] == 1;
                                        if ($isMedley) {
                                            echo '- ';
                                            echo ($url !== '#') ? '<a href="' . $url . '">' . htmlspecialchars($keyword, ENT_COMPAT, 'UTF-8') . '</a>' : htmlspecialchars($keyword, ENT_COMPAT, 'UTF-8');
                                            echo $feat . $ver . (!empty($annotation) ? ' ' . $annotation : '') . '<br>';
                                        } else {
                                            echo '<li>';
                                            echo ($url !== '#') ? '<a href="' . $url . '">' . htmlspecialchars($keyword, ENT_COMPAT, 'UTF-8') . '</a>' : htmlspecialchars($keyword, ENT_COMPAT, 'UTF-8');
                                            echo $feat . $ver . (!empty($annotation) ? ' ' . $annotation : '') . '</li>';
                                        }
                                    }
                                    echo '</ol></div>';
                                } else {
                                    // type:'song' — インターリーブ
                                    if (!$inSongList) { echo '<ol class="setlist">'; $inSongList = true; }
                                    $artistId = isset($data['artist']) ? (is_numeric($data['artist']) ? (int)$data['artist'] : null) : null;
                                    $artistName = $artistIdToName[$artistId] ?? ($data['artist'] ?? '');
                                    if (stripos($setlistsTitle, 'ap') !== false && stripos($artistName, '桜井') !== false) {
                                        $artistName = str_ireplace('桜井', '櫻井', $artistName);
                                    }
                                    $sv = $data['song'] ?? '';
                                    if (is_numeric($sv)) {
                                        $sm = \App\Models\SlSong::find($sv);
                                        $title = $sm ? $sm->title : $sv;
                                        $url = url('/setlists/songs/' . $sv);
                                    } else {
                                        $title = $sv;
                                        $clean = trim(preg_replace('/\s*\[[^\]]+\]/u', '', $title));
                                        $sm = \App\Models\SlSong::where('title', $clean)->when($artistId, fn($q) => $q->where('artist_id', $artistId))->first();
                                        $url = $sm ? url('/setlists/songs/' . $sm->id) : '#';
                                    }
                                    $parts = splitAnnotation($title);
                                    $keyword = $parts['main'];
                                    $annotation = $parts['annotation'];
                                    $isMedley = !empty($data['medley']) && $data['medley'] == 1;
                                    $artistDisplay = '';
                                    if ($artistName && $artistId) {
                                        $artistDisplay = ' <span style="color:#999;font-size:0.75em;"><a href="' . url('/setlists/artists', $artistId) . '" style="color:#999;">' . htmlspecialchars($artistName, ENT_COMPAT, 'UTF-8') . '</a></span>';
                                    } elseif ($artistName) {
                                        $artistDisplay = ' <span style="color:#999;font-size:0.75em;">' . htmlspecialchars($artistName, ENT_COMPAT, 'UTF-8') . '</span>';
                                    }
                                    if (!empty($data['featuring'])) {
                                        $artistDisplay .= ' <span style="color:#999;font-size:0.75em;">' . htmlspecialchars($data['featuring'], ENT_COMPAT, 'UTF-8') . '</span>';
                                    }
                                    if ($isMedley) {
                                        echo '- ';
                                        echo ($url !== '#') ? '<a href="' . $url . '">' . htmlspecialchars($keyword, ENT_COMPAT, 'UTF-8') . '</a>' : htmlspecialchars($keyword, ENT_COMPAT, 'UTF-8');
                                        echo $artistDisplay . (!empty($annotation) ? ' ' . $annotation : '') . '<br>';
                                    } else {
                                        echo '<li>';
                                        echo ($url !== '#') ? '<a href="' . $url . '">' . htmlspecialchars($keyword, ENT_COMPAT, 'UTF-8') . '</a>' : htmlspecialchars($keyword, ENT_COMPAT, 'UTF-8');
                                        echo $artistDisplay . (!empty($annotation) ? ' ' . $annotation : '') . '</li>';
                                    }
                                }
                            }
                            if ($inSongList) echo '</ol>';
                        }
                    @endphp

                    @php renderFesMixed($setlists->fes_setlist, $artistIdToName, $setlists->title); @endphp

                    @if (!empty($setlists->fes_encore))
                        <div style="margin: 0;">
                            <span style="color: #999; font-weight: 600; font-size: 0.9rem; letter-spacing: 2px;">ENCORE</span>
                        </div>
                        @php renderFesMixed($setlists->fes_encore, $artistIdToName, $setlists->title); @endphp
                    @endif

                @endif {{-- fes_type混合 終わり --}}

                @endif {{-- fes 終わり --}}

                </div>{{-- live-column --}}
                </div>{{-- setlist-row --}}

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