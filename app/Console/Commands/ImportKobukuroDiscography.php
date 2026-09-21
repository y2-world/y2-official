<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

class ImportKobukuroDiscography extends Command
{
    protected $signature = 'import:kobukuro-discography {--dry-run : Show matching without saving}';

    protected $description = 'Import コブクロ albums and singles, matching tracklist songs to existing DbSong records';

    private const ARTIST_ID = 6;

    private function normalize(string $title): string
    {
        $title = $this->normalizeKeepingSpaces($title);
        $title = preg_replace('/\s+/u', '', $title);
        return $title;
    }

    private function normalizeKeepingSpaces(string $title): string
    {
        $title = mb_convert_kana($title, 'as');
        $title = str_replace(['’', '‘'], "'", $title);
        $title = preg_replace('/[･・.]{2,}|…+/u', '…', $title);
        $title = mb_strtolower($title, 'UTF-8');
        return $title;
    }

    // DbSongに存在しない企画曲・導入トラック（既存曲をメドレー的に繋いだオーケストラアレンジや
    // 短いイントロ等）。単独の新曲として登録する性質のものではないため、idを持たせず
    // exceptionのみで表示する。
    private const NON_SONG_TRACKS = [
        '交響曲第5296番',
        '(Are you all set?)',
    ];

    private function findSong(array $songsByNormalizedTitle, array $songTitlesById, string $title): ?array
    {
        if (in_array($title, self::NON_SONG_TRACKS, true)) {
            return ['id' => null, 'exception' => $title];
        }

        $normalized = $this->normalize($title);
        if (isset($songsByNormalizedTitle[$normalized])) {
            $id = $songsByNormalizedTitle[$normalized];
            $baseTitle = $songTitlesById[$id];
            return ['id' => $id, 'exception' => ($baseTitle === $title) ? null : $title];
        }

        $titleForPrefixMatch = $this->normalizeKeepingSpaces($title);
        $bestMatchId = null;
        $bestMatchLength = 0;
        foreach ($songTitlesById as $id => $songTitle) {
            $songTitle = (string) $songTitle;
            if ($songTitle === '') {
                continue;
            }
            $normalizedSongTitle = $this->normalizeKeepingSpaces($songTitle);
            if (!str_starts_with($titleForPrefixMatch, $normalizedSongTitle)) {
                continue;
            }
            $rest = mb_substr($titleForPrefixMatch, mb_strlen($normalizedSongTitle, 'UTF-8'), null, 'UTF-8');
            if ($rest !== '' && !preg_match('/^[\s\(（\-〜～…]/u', $rest)) {
                continue;
            }
            if (mb_strlen($normalizedSongTitle, 'UTF-8') > $bestMatchLength) {
                $bestMatchId = $id;
                $bestMatchLength = mb_strlen($normalizedSongTitle, 'UTF-8');
            }
        }
        if ($bestMatchId !== null) {
            return ['id' => $bestMatchId, 'exception' => $title];
        }

        return null;
    }

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');

        $songs = DbSong::where('artist_id', self::ARTIST_ID)->get(['id', 'title']);
        $songsByNormalizedTitle = [];
        $songTitlesById = [];
        foreach ($songs as $s) {
            $songsByNormalizedTitle[$this->normalize($s->title)] = $s->id;
            $songTitlesById[$s->id] = $s->title;
        }

        $albums = $this->albumsData();
        $unmatched = [];
        $albumId = 0;

        foreach ($albums as $albumData) {
            $trackIds = [];
            foreach ($albumData['tracks'] as $trackEntry) {
                // tracksの各要素は曲名の文字列、または ['曲名', ディスク番号] の配列（複数ディスク構成のアルバム用）
                $trackTitle = is_array($trackEntry) ? $trackEntry[0] : $trackEntry;
                $disc = is_array($trackEntry) ? $trackEntry[1] : null;

                // 他アーティストのカバー曲を収録したアルバム（'covers' => true）は、曲名が
                // たまたまコブクロの既存曲と同名でも（例: ANSWER）別の曲のカバーのため、
                // findSongでマッチさせずリンクなしのプレーンテキストとして扱う
                if (!empty($albumData['covers'])) {
                    $track = ['exception' => $trackTitle];
                    if ($disc !== null) {
                        $track['disc'] = $disc;
                    }
                    $trackIds[] = $track;
                    continue;
                }

                $match = $this->findSong($songsByNormalizedTitle, $songTitlesById, $trackTitle);
                if ($match === null) {
                    $unmatched[] = $albumData['title'] . ' / ' . $trackTitle;
                    continue;
                }
                $isKaraoke = $match['id'] === null || isKaraokeTrack($match['exception']);
                $track = $isKaraoke ? [] : ['id' => $match['id']];
                if ($disc !== null) {
                    $track['disc'] = $disc;
                }
                if ($match['exception']) {
                    $track['exception'] = $match['exception'];
                }
                $trackIds[] = $track;
            }

            $isOriginal = empty($albumData['best']) && empty($albumData['mini']);
            if ($isOriginal) {
                $albumId++;
            }

            $this->line("=== {$albumData['title']} ({$albumData['date']}) === matched " . count($trackIds) . '/' . count($albumData['tracks']));

            if (!$dryRun) {
                $key = ['artist_id' => self::ARTIST_ID, 'date' => $albumData['date']];
                if ($isOriginal) {
                    $key['album_id'] = $albumId;
                } else {
                    // ベスト/ミニは基本dateのみで識別するが、同日に複数リリースがある場合は
                    // titleもキーに含めて区別する（例: ALL SINGLES BEST 2とFAN'S MADE BESTが同日）
                    $key['title'] = $albumData['title'];
                }
                DbAlbum::updateOrCreate(
                    $key,
                    [
                        'title' => $albumData['title'],
                        'album_id' => $isOriginal ? $albumId : null,
                        'best' => $albumData['best'] ?? false,
                        'mini' => $albumData['mini'] ?? false,
                        'tracklist' => $trackIds,
                    ]
                );
            }
        }

        $singles = $this->singlesData();
        $singleId = 0;

        foreach ($singles as $singleData) {
            $trackIds = [];
            foreach ($singleData['tracks'] as $trackTitle) {
                $match = $this->findSong($songsByNormalizedTitle, $songTitlesById, $trackTitle);
                if ($match === null) {
                    $unmatched[] = $singleData['title'] . ' / ' . $trackTitle;
                    continue;
                }
                $isKaraoke = $match['id'] === null || isKaraokeTrack($match['exception']);
                $track = $isKaraoke ? [] : ['id' => $match['id']];
                if ($match['exception']) {
                    $track['exception'] = $match['exception'];
                }
                $trackIds[] = $track;
            }
            // Blue Birdのデモ音源はDbSongに存在しない（正式収録曲ではないため登録しない）
            if ($singleData['title'] === 'Blue Bird') {
                $trackIds[] = ['exception' => 'ラブレター【demo】'];
            }

            // EPはCD/配信いずれの形態でもCDシングルの通し番号(single_id)には含めない
            $isEp = !empty($singleData['ep']);
            $isCd = empty($singleData['download']) && !$isEp;
            if ($isCd) {
                $singleId++;
            }

            $this->line("=== [single] {$singleData['title']} ({$singleData['date']}) === matched " . count($trackIds) . '/' . count($singleData['tracks']));

            if (!$dryRun) {
                $key = ['artist_id' => self::ARTIST_ID, 'date' => $singleData['date']];
                if ($isCd) {
                    $key['single_id'] = $singleId;
                }
                DbSingle::updateOrCreate(
                    $key,
                    [
                        'title' => $singleData['title'],
                        'single_id' => $isCd ? $singleId : null,
                        'download' => $singleData['download'] ?? false,
                        'tracklist' => $trackIds,
                    ]
                );
            }
        }

        if (count($unmatched)) {
            $this->warn('--- Unmatched tracks ---');
            foreach ($unmatched as $u) {
                $this->warn($u);
            }
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Import complete.');
    }

    private function albumsData(): array
    {
        return [
            // インディーズ盤（メジャーデビュー前、MINOSUKE RECORDSから自主制作でリリース）
            [
                'title' => 'Saturday 8:pm',
                'date' => '1999-07-21',
                'mini' => true,
                'tracks' => ['ボクノイバショ', '夢唄', 'ストリートのテーマ', '虹の真下', '遠くで・・'],
            ],
            [
                'title' => 'Root of my mind',
                'date' => '2000-03-04',
                'mini' => true,
                'tracks' => ['2人', 'LOVE', 'DOOR', 'Bye Bye Oh! Dear My Lover', '桜', '轍-わだち-', '赤い糸'],
            ],
            [
                'title' => 'ANSWER',
                'date' => '2000-12-19',
                'mini' => true,
                'tracks' => ['遠まわり', '光', '心に笑みを', '坂道', '神風', 'そばにいれるなら', 'Moon Light Party!!'],
            ],
            [
                'title' => 'Roadmade',
                'date' => '2001-08-29',
                'tracks' => ['YELL〜エール〜', '朝顔', 'コンパス', 'そばにおいで', 'Ring', 'memory', '2人', 'miss you', '轍-わだち-', 'ANSWER'],
            ],
            [
                'title' => 'grapefruits',
                'date' => '2002-08-28',
                'tracks' => ['新しい場所', '彼女', 'Overflow', '願いの詩', 'GRAPEFRUITS DAYS', '小渕君の犬のうた', 'YOU', 'アンブレラ', '太陽', '光', '翼よあれは巴里の灯だ', '風'],
            ],
            [
                'title' => 'STRAIGHT',
                'date' => '2003-11-06',
                'tracks' => ['blue blue', '手紙', '真実の口', '雪の降らない街', 'まーだだよ', 'INVISIBLE MAN', '愛する人よ', '背番号1', '昨日の日のワルツ', '宝島', 'Holy Snowy Night', 'STRAIGHT'],
            ],
            [
                'title' => 'MUSIC MAN SHIP',
                'date' => '2004-11-03',
                'tracks' => ['東京の冬', 'Million Films', 'ボーイズ・オン・ザ・ラン', '永遠にともに', 'Rising', '光の誓いが聴こえた日', 'DOOR', 'エピローグ', 'HUMMING LIFE', 'この指とまれ!', 'ここから', '毎朝、ボクの横にいて -Sweet drip mix-'],
            ],
            [
                'title' => 'NAMELESS WORLD',
                'date' => '2005-12-21',
                'tracks' => ['Flag', '桜', '六等星', 'ここにしか咲かない花', '待夢磨心 -タイムマシン-', 'Pierrot', 'Saturday', '大樹の影', 'NOTE', 'Starting Line', 'LOVER’S SURF', '同じ窓から見てた空'],
            ],
            [
                'title' => '5296',
                'date' => '2007-12-19',
                'tracks' => ['蒼く 優しく', 'コイン', '蕾', 'どんな空でも', '君という名の翼', 'WHITE DAYS', '君色', '水面の蝶', '風の中を', '月光', '風見鶏', 'Diary', 'Fragile mind'],
            ],
            [
                'title' => 'CALLING',
                'date' => '2009-08-05',
                'tracks' => ['サヨナラ HERO', '恋心', 'To calling of love', '虹', 'STAY', '天使達の歌', 'FREEDOM TRAIN', 'Summer rain', 'Sunday kitchen', '神風', 'ベテルギウス', '時の足音', '赤い糸'],
            ],
            [
                'title' => 'One Song From Two Hearts',
                'date' => '2013-12-18',
                'tracks' => ['(Are you all set?)', 'One Song From Two Hearts', '紙飛行機', 'リンゴの花', 'ダイヤモンド', 'SPLASH', '未来切手', 'モノクローム', 'あの太陽が、この世界を照らし続けるように。', 'GAME', '流星', 'Blue Bird', 'LIFE GOES ON', '蜜蜂', '今、咲き誇る花たちよ'],
            ],
            [
                'title' => 'TIMELESS WORLD',
                'date' => '2016-06-15',
                'tracks' => ['SUNRISE', '未来', '何故、旅をするのだろう', 'tOKi meki', 'SNIFF OUT!', 'サイ(レ)ン', 'hana', '星が綺麗な夜でした', 'Twilight', 'Tearless', '陽だまりの道', '42.195km', '奇跡', 'NO PAIN, NO GAIN feat. 布袋寅泰', 'STAGE'],
            ],
            [
                'title' => 'Star Made',
                'date' => '2021-08-04',
                'tracks' => ['Star Song', '風をみつめて', '卒業', 'ONE TIMES ONE', '両忘', '灯ル祈リ', '露光', '晴々', '夕紅', '心', '君になれ', '大阪SOUL', '白雪', 'バトン', 'Always (laughing with you.)'],
            ],
            [
                'title' => 'QUARTER CENTURY',
                'date' => '2024-09-04',
                'tracks' => ['RAISE THE ANCHOR', 'エンベロープ', 'Mr.GLORY', 'Soul to Soul (布袋寅泰 feat. コブクロ)', 'Blame It On The Love Song', '雨', '足跡', 'ベテルギウス', '雨粒と花火', 'Moon Light Party!!', 'Days', 'この地球の続きを'],
            ],
            [
                'title' => 'THIS IS MY HOMETOWN',
                'date' => '2025-07-16',
                'mini' => true,
                'tracks' => ['THIS IS MY HOMETOWN', 'この地球の続きを', '大阪恋物語 -Refined the live take-', '42.195km', 'おさかなにわ', '大阪SOUL'],
            ],
            // ベストアルバム
            [
                'title' => 'ALL SINGLES BEST',
                'date' => '2006-09-27',
                'best' => true,
                'tracks' => [
                    ['君という名の翼', 'Disc-1'], ['あなたへと続く道', 'Disc-1'], ['ここにしか咲かない花', 'Disc-1'], ['毎朝、ボクの横にいて -Sweet drip mix-', 'Disc-1'], ['Million Films', 'Disc-1'], ['永遠にともに', 'Disc-1'], ['blue blue', 'Disc-1'], ['宝島', 'Disc-1'], ['雪の降らない街', 'Disc-1'], ['願いの詩', 'Disc-1'],
                    ['風', 'Disc-2'], ['YOU', 'Disc-2'], ['miss you', 'Disc-2'], ['YELL〜エール〜', 'Disc-2'], ['Bell', 'Disc-2'], ['轍-わだち-', 'Disc-2'], ['DOOR', 'Disc-2'], ['太陽', 'Disc-2'], ['桜', 'Disc-2'], ['未来への帰り道', 'Disc-2'],
                ],
            ],
            [
                'title' => 'ALL COVERS BEST',
                'date' => '2010-08-25',
                'best' => true,
                'covers' => true,
                'tracks' => [
                    ['奇跡の地球', 'Disc-1'], ['I LOVE YOU', 'Disc-1'], ['いつまでも変わらぬ愛を', 'Disc-1'], ['SUPERSTITION', 'Disc-1'], ['ALONE AGAIN (NATURALLY)', 'Disc-1'], ['遠い恋のリフレイン', 'Disc-1'], ['もうひとつの土曜日', 'Disc-1'], ['The Thrill is gone', 'Disc-1'], ['Lovin\' you', 'Disc-1'], ['こんな風にして終わるもの', 'Disc-1'], ['THE ONLY THING THAT LOOKS GOOD ON ME IS YOU', 'Disc-1'], ['CALIFORNIA SUN', 'Disc-1'], ['ANSWER', 'Disc-1'],
                    ['WHAT A WONDERFUL WORLD', 'Disc-2'], ['三日月', 'Disc-2'], ['DESPERADO', 'Disc-2'], ['Pink Prisoner', 'Disc-2'], ['気球に乗って', 'Disc-2'], ['ON MY BEAT', 'Disc-2'], ['IF IT MAKES YOU HAPPY', 'Disc-2'], ['LAYLA', 'Disc-2'], ['卒業写真', 'Disc-2'], ['運命船サラバ号出発', 'Disc-2'], ['The Love We Make', 'Disc-2'], ['しあわせのランプ', 'Disc-2'],
                ],
            ],
            [
                'title' => 'ALL SINGLES BEST 2',
                'date' => '2012-09-05',
                'best' => true,
                'tracks' => [
                    ['蕾', 'Disc-1'], ['風見鶏', 'Disc-1'], ['蒼く 優しく', 'Disc-1'], ['時の足音', 'Disc-1'], ['赤い糸', 'Disc-1'], ['ベテルギウス', 'Disc-1'], ['虹', 'Disc-1'], ['Summer rain', 'Disc-1'], ['STAY', 'Disc-1'], ['WINDING ROAD', 'Disc-1'], ['あなたと', 'Disc-1'],
                    ['流星', 'Disc-2'], ['Blue Bird', 'Disc-2'], ['君への主題歌', 'Disc-2'], ['あの太陽が、この世界を照らし続けるように。', 'Disc-2'], ['シルエット', 'Disc-2'], ['蜜蜂', 'Disc-2'], ['焚き火の様な歌', 'Disc-2'], ['ココロの羽', 'Disc-2'], ['太陽のメロディー', 'Disc-2'], ['交響曲第5296番', 'Disc-2'],
                ],
            ],
            [
                'title' => "FAN'S MADE BEST",
                'date' => '2012-09-05',
                'best' => true,
                'tracks' => [
                    ['ココロの羽', 'Disc-1'], ['夜空', 'Disc-1'], ['NOTE', 'Disc-1'], ['潮騒ドライブ', 'Disc-1'], ['ストリートのテーマ (from 2008.9.6 和歌山県紀三井寺運動公園陸上競技場)', 'Disc-1'], ['光の粒', 'Disc-1'], ['今と未来を繋ぐもの', 'Disc-1'], ['どんな空でも (from 2008.6.5 大阪城ホール)', 'Disc-1'], ['夢唄〜Back to street〜', 'Disc-1'], ['そばにいれるなら…', 'Disc-1'], ['ANSWER', 'Disc-1'], ['光', 'Disc-1'],
                    ['手紙', 'Disc-2'], ['向かい風', 'Disc-2'], ['エピローグ', 'Disc-2'], ['To calling of love (from 2009.11.28 日本武道館)', 'Disc-2'], ['遠くで・・ (from 2006.5.6 日本武道館)', 'Disc-2'], ['Happy Birthday', 'Disc-2'], ['彼方へ', 'Disc-2'], ['同じ窓から見てた空', 'Disc-2'], ['神風', 'Disc-2'], ['愛する人よ (Studio Live)', 'Disc-2'], ['WHITE DAYS', 'Disc-2'], ['Flag', 'Disc-2'],
                ],
            ],
            [
                'title' => 'ALL TIME BEST 1998-2018',
                'date' => '2018-12-05',
                'best' => true,
                'tracks' => [
                    ['桜', 'Disc-1'], ['赤い糸', 'Disc-1'], ['太陽', 'Disc-1'], ['轍-わだち-', 'Disc-1'], ['DOOR', 'Disc-1'], ['Bell', 'Disc-1'], ['ココロの羽', 'Disc-1'], ['YELL〜エール〜', 'Disc-1'], ['miss you', 'Disc-1'], ['光', 'Disc-1'], ['ANSWER', 'Disc-1'], ['YOU', 'Disc-1'], ['風', 'Disc-1'], ['願いの詩', 'Disc-1'],
                    ['雪の降らない街', 'Disc-2'], ['愛する人よ', 'Disc-2'], ['宝島', 'Disc-2'], ['blue blue', 'Disc-2'], ['手紙', 'Disc-2'], ['永遠にともに', 'Disc-2'], ['Million Films', 'Disc-2'], ['ここにしか咲かない花', 'Disc-2'], ['あなたへと続く道', 'Disc-2'], ['君という名の翼', 'Disc-2'], ['風見鶏', 'Disc-2'], ['蕾', 'Disc-2'], ['WHITE DAYS', 'Disc-2'], ['蒼く 優しく', 'Disc-2'],
                    ['時の足音', 'Disc-3'], ['虹', 'Disc-3'], ['STAY', 'Disc-3'], ['To calling of love', 'Disc-3'], ['流星', 'Disc-3'], ['Blue Bird', 'Disc-3'], ['あの太陽が、この世界を照らし続けるように。', 'Disc-3'], ['蜜蜂', 'Disc-3'], ['紙飛行機', 'Disc-3'], ['One Song From Two Hearts', 'Disc-3'], ['ダイヤモンド', 'Disc-3'], ['今、咲き誇る花たちよ', 'Disc-3'], ['陽だまりの道', 'Disc-3'], ['Twilight', 'Disc-3'], ['42.195km', 'Disc-3'],
                    ['奇跡', 'Disc-4'], ['hana', 'Disc-4'], ['未来', 'Disc-4'], ['SNIFF OUT!', 'Disc-4'], ['STAGE', 'Disc-4'], ['心', 'Disc-4'], ['バトン', 'Disc-4'], ['君になれ', 'Disc-4'], ['ONE TIMES ONE', 'Disc-4'], ['晴々', 'Disc-4'], ['風をみつめて', 'Disc-4'], ['木蘭の涙', 'Disc-4'], ['WINDING ROAD', 'Disc-4'], ['太陽のメロディー', 'Disc-4'], ['桜-1st demo tape-', 'Disc-4'],
                ],
            ],
            [
                'title' => 'ALL SEASONS BEST',
                'date' => '2024-03-20',
                'best' => true,
                'tracks' => [
                    ['桜', 'Disc-1'], ['蕾', 'Disc-1'], ['未来', 'Disc-1'], ['YELL〜エール〜', 'Disc-1'], ['轍-わだち-', 'Disc-1'], ['風', 'Disc-1'], ['虹', 'Disc-1'], ['卒業', 'Disc-1'], ['風見鶏', 'Disc-1'], ['WINDING ROAD（絢香×コブクロ）', 'Disc-1'], ['卒業（SANA from TWICE×コブクロ）', 'Disc-1'], ['ここにしか咲かない花', 'Disc-1'],
                    ['君という名の翼', 'Disc-2'], ['願いの詩', 'Disc-2'], ['太陽', 'Disc-2'], ['ダイヤモンド', 'Disc-2'], ['同じ窓から見てた空', 'Disc-2'], ['Summer rain', 'Disc-2'], ['潮騒ドライブ', 'Disc-2'], ['memory', 'Disc-2'], ['HUMMING LIFE', 'Disc-2'], ['夏の雫', 'Disc-2'], ['LOVER’S SURF', 'Disc-2'], ['晴々', 'Disc-2'],
                    ['Million Films', 'Disc-3'], ['未来への帰り道', 'Disc-3'], ['blue blue', 'Disc-3'], ['風をみつめて', 'Disc-3'], ['NOTE', 'Disc-3'], ['コイン', 'Disc-3'], ['2人', 'Disc-3'], ['何故、旅をするのだろう', 'Disc-3'], ['FREEDOM TRAIN', 'Disc-3'], ['夕紅', 'Disc-3'], ['紙飛行機', 'Disc-3'], ['陽だまりの道', 'Disc-3'],
                    ['赤い糸', 'Disc-4'], ['流星', 'Disc-4'], ['今、咲き誇る花たちよ', 'Disc-4'], ['STAY', 'Disc-4'], ['あなたへと続く道', 'Disc-4'], ['WHITE DAYS', 'Disc-4'], ['Twilight', 'Disc-4'], ['雪の降らない街', 'Disc-4'], ['東京の冬', 'Disc-4'], ['天使達の歌', 'Disc-4'], ['白雪', 'Disc-4'], ['海に降る雪', 'Disc-4'], ['光の粒', 'Disc-4'],
                ],
            ],
        ];
    }

    private function singlesData(): array
    {
        return [
            ['title' => 'YELL〜エール〜 / Bell', 'date' => '2001-03-22', 'tracks' => ['YELL〜エール〜', 'Bell', 'YELL 〜エール〜 (Instrumental)', 'Bell (Instrumental)']],
            ['title' => '轍-わだち-', 'date' => '2001-06-20', 'tracks' => ['轍-わだち-', '遠まわり', '轍 -わだち- (Instrumental)', '遠まわり (Instrumental)']],
            ['title' => 'YOU / miss you', 'date' => '2001-11-21', 'tracks' => ['YOU', 'miss you', '海に降る雪', 'YOU (Instrumental)', 'miss you (Instrumental)', '海に降る雪 (Instrumental)']],
            ['title' => '風', 'date' => '2002-02-14', 'tracks' => ['風', 'そしてまた恋をする', '風 (Instrumental)', 'そしてまた恋をする (Instrumental)']],
            ['title' => '願いの詩 / 太陽', 'date' => '2002-07-10', 'tracks' => ['願いの詩', '太陽', 'ゆらゆら', '願いの詩 (Instrumental)', '太陽 (Instrumental)']],
            ['title' => '雪の降らない街', 'date' => '2002-11-13', 'tracks' => ['雪の降らない街', 'The Big Man’s Blues', '雪の降らない街 (Instrumental)', 'The Big Man’s Blues (Instrumental)']],
            ['title' => '宝島', 'date' => '2003-04-09', 'tracks' => ['宝島', '愛する人よ', '宝島 (Instrumental)', '愛する人よ (Instrumental)']],
            ['title' => 'blue blue', 'date' => '2003-08-27', 'tracks' => ['blue blue', '潮騒ドライブ']],
            ['title' => 'DOOR', 'date' => '2004-05-12', 'tracks' => ['DOOR', '忘れてはいけないもの', 'DOOR (Instrumental)', '忘れてはいけないもの (Instrumental)']],
            ['title' => '永遠にともに / Million Films', 'date' => '2004-10-14', 'tracks' => ['永遠にともに', 'Million Films', 'ここから', '永遠にともに (Instrumental)', 'Million Films (Instrumental)', 'ここから (Instrumental)']],
            ['title' => 'ここにしか咲かない花', 'date' => '2005-05-11', 'tracks' => ['ここにしか咲かない花', '六等星', 'ここにしか咲かない花 (Instrumental)', '六等星 (Instrumental)']],
            ['title' => '桜', 'date' => '2005-11-02', 'tracks' => ['桜', '今と未来を繋ぐもの', 'Starting Line', '桜 (Instrumental)', '今と未来を繋ぐもの (Instrumental)', 'Starting Line (Instrumental)']],
            ['title' => '君という名の翼', 'date' => '2006-07-26', 'tracks' => ['君という名の翼', 'あなたへと続く道', '君という名の翼 (Instrumental)', 'あなたへと続く道 (Instrumental)']],
            ['title' => '蕾', 'date' => '2007-03-21', 'tracks' => ['蕾', '彼方へ', '風見鶏', '蕾 (Instrumental)', '彼方へ (Instrumental)', '風見鶏 (Instrumental)']],
            ['title' => '蒼く 優しく', 'date' => '2007-11-07', 'tracks' => ['蒼く 優しく', '君色', '赤い糸 ～Live at 大阪城ホール 2007.07.05～', '蒼く 優しく (Instrumental)', '君色 (Instrumental)']],
            ['title' => '時の足音', 'date' => '2008-10-29', 'tracks' => ['時の足音', '赤い糸', 'ベテルギウス', '時の足音 (Instrumental)', '赤い糸 (Instrumental)', 'ベテルギウス (Instrumental)']],
            ['title' => '虹', 'date' => '2009-04-15', 'tracks' => ['虹', 'Summer rain', 'ルルル', '虹 (Instrumental)', 'Summer rain (Instrumental)', 'ルルル (Instrumental)']],
            ['title' => 'STAY', 'date' => '2009-07-15', 'tracks' => ['STAY', 'FREEDOM TRAIN', '君といたいのに', 'STAY (Instrumental)', 'FREEDOM TRAIN (Instrumental)']],
            ['title' => '流星', 'date' => '2010-11-17', 'tracks' => ['流星', '流星 (Instrumental)']],
            ['title' => 'Blue Bird', 'date' => '2011-02-16', 'tracks' => ['Blue Bird', '君への主題歌', 'Blue Bird (Instrumental)', '君への主題歌 (Instrumental)']],
            ['title' => 'あの太陽が、この世界を照らし続けるように。', 'date' => '2011-04-27', 'tracks' => ['あの太陽が、この世界を照らし続けるように。', 'シルエット', 'あの太陽が、この世界を照らし続けるように。 (Instrumental)', 'シルエット (Instrumental)']],
            ['title' => '蜜蜂', 'date' => '2012-01-27', 'download' => true, 'tracks' => ['蜜蜂']],
            ['title' => '紙飛行機', 'date' => '2012-11-28', 'tracks' => ['紙飛行機', '紙飛行機 (Instrumental)']],
            ['title' => '三つ葉のクローバー', 'date' => '2013-06-23', 'download' => true, 'tracks' => ['三つ葉のクローバー']],
            ['title' => 'One Song From Two Hearts / ダイヤモンド', 'date' => '2013-07-24', 'tracks' => ['One Song From Two Hearts', 'ダイヤモンド', 'ラブレター', 'One Song From Two Hearts (Instrumental)', 'ダイヤモンド (Instrumental)', 'ラブレター (Instrumental)']],
            ['title' => '今、咲き誇る花たちよ', 'date' => '2014-02-19', 'tracks' => ['今、咲き誇る花たちよ', '今、咲き誇る花たちよ (Instrumental)']],
            ['title' => '陽だまりの道', 'date' => '2014-06-04', 'tracks' => ['陽だまりの道', 'BEST FRIEND', 'サイ(レ)ン', '陽だまりの道 (Instrumental)', 'BEST FRIEND (Instrumental)', 'サイ(レ)ン (Instrumental)']],
            ['title' => '42.195km', 'date' => '2014-10-15', 'download' => true, 'tracks' => ['42.195km']],
            ['title' => 'Twilight', 'date' => '2014-11-08', 'download' => true, 'tracks' => ['Twilight']],
            ['title' => '奇跡', 'date' => '2015-03-04', 'tracks' => ['奇跡', 'Twilight', '信呼吸', '奇跡 (Instrumental)', 'Twilight (Instrumental)', '信呼吸 (Instrumental)']],
            ['title' => 'hana', 'date' => '2015-04-20', 'download' => true, 'tracks' => ['hana', 'hana (Instrumental)']],
            ['title' => '未来', 'date' => '2015-12-16', 'tracks' => ['未来', '未来 (Instrumental)']],
            ['title' => 'SNIFF OUT!', 'date' => '2016-02-10', 'download' => true, 'tracks' => ['SNIFF OUT!']],
            ['title' => '心', 'date' => '2017-05-24', 'tracks' => ['心', 'HELLO, NEW DAY', 'LIFE', '心 (Instrumental)', 'HELLO, NEW DAY (Instrumental)', 'LIFE (Instrumental)']],
            ['title' => 'ONE TIMES ONE', 'date' => '2018-04-11', 'tracks' => ['ONE TIMES ONE', 'バトン', '君になれ', '君という名の翼 (LIVE from 2017.8.21 東京国際フォーラムA)', '蒼く 優しく (LIVE from 2017.8.22 東京国際フォーラムA)', '心 (LIVE from 2017.8.22 東京国際フォーラムA)', '蕾 (LIVE from 2017.10.28 東京ドーム)', 'STAGE Acoustic ver. (LIVE from 2016.6.7 シダックスカルチャーホール)', 'ONE TIMES ONE (Instrumental)', 'バトン (Instrumental)', '君になれ (Instrumental)']],
            ['title' => '晴々', 'date' => '2018-09-17', 'download' => true, 'tracks' => ['晴々']],
            ['title' => '風をみつめて', 'date' => '2018-11-07', 'tracks' => ['風をみつめて', '夏の雫', '白雪', '風をみつめて (Instrumental)', '夏の雫 (Instrumental)', '白雪 (Instrumental)']],
            ['title' => '大阪SOUL', 'date' => '2019-12-04', 'download' => true, 'tracks' => ['大阪SOUL']],
            ['title' => '卒業', 'date' => '2020-03-18', 'tracks' => ['卒業', '大阪SOUL', '卒業〜合唱〜', '卒業 (Instrumental)', '大阪SOUL (Instrumental)']],
            ['title' => '灯ル祈リ', 'date' => '2020-10-14', 'tracks' => ['灯ル祈リ', 'Lullaby', '灯ル祈リ (Instrumental)', 'Lullaby (Instrumental)']],
            ['title' => '両忘', 'date' => '2021-07-07', 'tracks' => ['両忘', 'STAY（ツマビクウタゴエver.）', 'YOU（ツマビクウタゴエver.）', '両忘 (Instrumental)']],
            ['title' => 'Days', 'date' => '2022-03-30', 'download' => true, 'tracks' => ['Days']],
            ['title' => 'この地球の続きを', 'date' => '2022-07-19', 'download' => true, 'tracks' => ['この地球の続きを']],
            ['title' => 'この地球の続きを', 'date' => '2022-10-19', 'tracks' => ['この地球の続きを', 'Days', '恋愛観測 (LIVE ver. from KOBUKURO LIVE TOUR 2011)', 'この地球の続きを (Instrumental)', 'Days (Instrumental)']],
            ['title' => 'エンベロープ', 'date' => '2023-03-01', 'tracks' => ['エンベロープ', 'ベテルギウス (LIVE at 大阪城ホール 2022.11.01)', 'あの太陽が、この世界を照らし続けるように。 (LIVE at さいたまスーパーアリーナ 2022.11.20)', '時の足音 (LIVE at さいたまスーパーアリーナ 2022.11.20)', 'エンベロープ (Instrumental)']],
            ['title' => 'Starry Smile Story', 'date' => '2026-03-22', 'download' => true, 'tracks' => ['Starry Smile Story']],
            ['title' => '霞日和 / Starry Smile Story', 'date' => '2026-07-22', 'tracks' => ['霞日和', 'Starry Smile Story', 'Message Card', '霞日和 (Instrumental)', 'Starry Smile Story (Instrumental)', 'Message Card (Instrumental)']],
        ];
    }
}
