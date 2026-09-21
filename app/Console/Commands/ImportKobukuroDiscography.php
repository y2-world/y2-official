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

    private function findSong(array $songsByNormalizedTitle, array $songTitlesById, string $title): ?array
    {
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
            if ($rest !== '' && !preg_match('/^[\s\(（\-〜～]/u', $rest)) {
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
            foreach ($albumData['tracks'] as $trackTitle) {
                $match = $this->findSong($songsByNormalizedTitle, $songTitlesById, $trackTitle);
                if ($match === null) {
                    $unmatched[] = $albumData['title'] . ' / ' . $trackTitle;
                    continue;
                }
                $isKaraoke = isKaraokeTrack($match['exception']);
                $track = $isKaraoke ? [] : ['id' => $match['id']];
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
                $isKaraoke = isKaraokeTrack($match['exception']);
                $track = $isKaraoke ? [] : ['id' => $match['id']];
                if ($match['exception']) {
                    $track['exception'] = $match['exception'];
                }
                $trackIds[] = $track;
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
                'tracks' => ['One Song From Two Hearts', '紙飛行機', 'リンゴの花', 'ダイヤモンド', 'SPLASH', '未来切手', 'モノクローム', 'あの太陽が、この世界を照らし続けるように。', 'GAME', '流星', 'Blue Bird', 'LIFE GOES ON', '蜜蜂', '今、咲き誇る花たちよ'],
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
                'tracks' => ['RAISE THE ANCHOR', 'エンベロープ', 'Mr.GLORY', 'Soul to Soul (布袋寅泰 feat.コブクロ)', 'Blame It On The Love Song', '雨', '足跡', 'ベテルギウス', '雨粒と花火', 'Moon Light Party!!', 'Days', 'この地球の続きを'],
            ],
            // ベストアルバム
            [
                'title' => 'ALL SINGLES BEST',
                'date' => '2006-09-27',
                'best' => true,
                'tracks' => [
                    '君という名の翼', 'あなたへと続く道', 'ここにしか咲かない花', '毎朝、ボクの横にいて -Sweet drip mix-', 'Million Films', '永遠にともに', 'blue blue', '宝島', '雪の降らない街', '願いの詩',
                    '風', 'YOU', 'miss you', 'YELL〜エール〜', 'Bell', '轍-わだち-', 'DOOR', '太陽', '桜', '未来への帰り道',
                ],
            ],
            [
                'title' => 'ALL SINGLES BEST 2',
                'date' => '2012-09-05',
                'best' => true,
                'tracks' => [
                    '蕾', '風見鶏', '蒼く 優しく', '時の足音', '赤い糸', 'ベテルギウス', '虹', 'Summer rain', 'STAY',
                    '流星', 'Blue Bird', '君への主題歌', 'あの太陽が、この世界を照らし続けるように。', 'シルエット', '蜜蜂', '焚き火の様な歌', 'ココロの羽',
                ],
            ],
            [
                'title' => 'ALL TIME BEST 1998-2018',
                'date' => '2018-12-05',
                'best' => true,
                'tracks' => [
                    '桜', '赤い糸', '太陽', '轍-わだち-', 'DOOR', 'Bell', 'ココロの羽', 'YELL〜エール〜', 'miss you', '光', 'ANSWER', 'YOU', '風', '願いの詩',
                    '雪の降らない街', '愛する人よ', '宝島', 'blue blue', '手紙', '永遠にともに', 'Million Films', 'ここにしか咲かない花', 'あなたへと続く道', '君という名の翼', '風見鶏', '蕾', 'WHITE DAYS', '蒼く 優しく',
                    '時の足音', '虹', 'STAY', 'To calling of love', '流星', 'Blue Bird', 'あの太陽が、この世界を照らし続けるように。', '蜜蜂', '紙飛行機', 'One Song From Two Hearts', 'ダイヤモンド', '今、咲き誇る花たちよ', '陽だまりの道', 'Twilight', '42.195km',
                    '奇跡', 'hana', '未来', 'SNIFF OUT!', 'STAGE', '心', 'バトン', '君になれ', 'ONE TIMES ONE', '晴々', '風をみつめて',
                ],
            ],
            [
                'title' => 'ALL SEASONS BEST',
                'date' => '2024-03-20',
                'best' => true,
                'tracks' => [
                    '桜', '蕾', '未来', 'YELL〜エール〜', '轍-わだち-', '風', '虹', '卒業', '風見鶏', 'ここにしか咲かない花',
                    '君という名の翼', '願いの詩', '太陽', 'ダイヤモンド', '同じ窓から見てた空', 'Summer rain', '潮騒ドライブ', 'memory', 'HUMMING LIFE', '夏の雫', 'LOVER’S SURF', '晴々',
                    'Million Films', '未来への帰り道', 'blue blue', '風をみつめて', 'NOTE', 'コイン', '2人', '何故、旅をするのだろう', 'FREEDOM TRAIN', '夕紅', '紙飛行機', '陽だまりの道',
                    '赤い糸', '流星', '今、咲き誇る花たちよ', 'STAY', 'あなたへと続く道', 'WHITE DAYS', 'Twilight', '雪の降らない街', '東京の冬', '天使達の歌', '白雪', '海に降る雪', '光の粒',
                ],
            ],
        ];
    }

    private function singlesData(): array
    {
        return [
            ['title' => 'YELL〜エール〜/Bell', 'date' => '2001-03-22', 'tracks' => ['YELL〜エール〜', 'Bell', 'YELL 〜エール〜 (Instrumental)', 'Bell (Instrumental)']],
            ['title' => '轍-わだち-', 'date' => '2001-06-20', 'tracks' => ['轍-わだち-', '遠まわり', '轍 -わだち- (Instrumental)', '遠まわり (Instrumental)']],
            ['title' => 'YOU/miss you', 'date' => '2001-11-21', 'tracks' => ['YOU', 'miss you', '海に降る雪', 'YOU (Instrumental)', 'miss you (Instrumental)', '海に降る雪 (Instrumental)']],
            ['title' => '風', 'date' => '2002-02-14', 'tracks' => ['風', 'そしてまた恋をする', '風 (Instrumental)', 'そしてまた恋をする (Instrumental)']],
            ['title' => '願いの詩/太陽', 'date' => '2002-07-10', 'tracks' => ['願いの詩', '太陽', 'ゆらゆら', '願いの詩 (Instrumental)', '太陽 (Instrumental)']],
            ['title' => '雪の降らない街', 'date' => '2002-11-13', 'tracks' => ['雪の降らない街', 'The Big Man’s Blues', '雪の降らない街 (Instrumental)', 'The Big Man’s Blues (Instrumental)']],
            ['title' => '宝島', 'date' => '2003-04-09', 'tracks' => ['宝島', '愛する人よ', '宝島 (Instrumental)', '愛する人よ (Instrumental)']],
            ['title' => 'blue blue', 'date' => '2003-08-27', 'tracks' => ['blue blue', '潮騒ドライブ']],
            ['title' => 'DOOR', 'date' => '2004-05-12', 'tracks' => ['DOOR', '忘れてはいけないもの', 'DOOR (Instrumental)', '忘れてはいけないもの (Instrumental)']],
            ['title' => '永遠にともに/Million Films', 'date' => '2004-10-14', 'tracks' => ['永遠にともに', 'Million Films', 'ここから', '永遠にともに (Instrumental)', 'Million Films (Instrumental)', 'ここから (Instrumental)']],
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
            ['title' => 'One Song From Two Hearts/ダイヤモンド', 'date' => '2013-07-24', 'tracks' => ['One Song From Two Hearts', 'ダイヤモンド', 'ラブレター', 'One Song From Two Hearts (Instrumental)', 'ダイヤモンド (Instrumental)', 'ラブレター (Instrumental)']],
            ['title' => '今、咲き誇る花たちよ', 'date' => '2014-02-19', 'tracks' => ['今、咲き誇る花たちよ', '今、咲き誇る花たちよ (Instrumental)']],
            ['title' => '陽だまりの道', 'date' => '2014-06-04', 'tracks' => ['陽だまりの道', 'BEST FRIEND', 'サイ(レ)ン', '陽だまりの道 (Instrumental)', 'BEST FRIEND (Instrumental)', 'サイ(レ)ン (Instrumental)']],
            ['title' => '42.195km', 'date' => '2014-10-15', 'download' => true, 'tracks' => ['42.195km']],
            ['title' => 'Twilight', 'date' => '2014-11-08', 'download' => true, 'tracks' => ['Twilight']],
            ['title' => '奇跡', 'date' => '2015-03-04', 'tracks' => ['奇跡', 'Twilight', '信呼吸', '奇跡 (Instrumental)', 'Twilight (Instrumental)', '信呼吸 (Instrumental)']],
            ['title' => 'hana', 'date' => '2015-04-20', 'download' => true, 'tracks' => ['hana']],
            ['title' => '未来', 'date' => '2015-12-16', 'tracks' => ['未来', '未来 (Instrumental)']],
            ['title' => 'SNIFF OUT!', 'date' => '2016-02-10', 'download' => true, 'tracks' => ['SNIFF OUT!']],
            ['title' => '心', 'date' => '2017-05-24', 'tracks' => ['心', 'HELLO, NEW DAY', 'LIFE', '心 (Instrumental)', 'HELLO, NEW DAY (Instrumental)', 'LIFE (Instrumental)']],
            ['title' => 'ONE TIMES ONE', 'date' => '2018-04-11', 'tracks' => ['ONE TIMES ONE', 'バトン', '君になれ', 'ONE TIMES ONE (Instrumental)', 'バトン (Instrumental)', '君になれ (Instrumental)']],
            ['title' => '晴々', 'date' => '2018-09-17', 'download' => true, 'tracks' => ['晴々']],
            ['title' => '風をみつめて', 'date' => '2018-11-07', 'tracks' => ['風をみつめて', '夏の雫', '白雪', '風をみつめて (Instrumental)', '夏の雫 (Instrumental)', '白雪 (Instrumental)']],
            ['title' => '大阪SOUL', 'date' => '2019-12-04', 'download' => true, 'tracks' => ['大阪SOUL']],
            ['title' => '卒業', 'date' => '2020-03-18', 'tracks' => ['卒業', '大阪SOUL', '卒業 (Instrumental)', '大阪SOUL (Instrumental)']],
            ['title' => '灯ル祈リ', 'date' => '2020-10-14', 'tracks' => ['灯ル祈リ', 'Lullaby', '灯ル祈リ (Instrumental)', 'Lullaby (Instrumental)']],
            ['title' => '両忘', 'date' => '2021-07-07', 'tracks' => ['両忘', '両忘 (Instrumental)']],
            ['title' => 'Days', 'date' => '2022-03-30', 'download' => true, 'tracks' => ['Days']],
            ['title' => 'この地球の続きを', 'date' => '2022-07-19', 'download' => true, 'tracks' => ['この地球の続きを']],
            ['title' => 'この地球の続きを', 'date' => '2022-10-19', 'tracks' => ['この地球の続きを', 'Days', 'この地球の続きを (Instrumental)', 'Days (Instrumental)']],
            ['title' => 'エンベロープ', 'date' => '2023-03-01', 'tracks' => ['エンベロープ', 'エンベロープ (Instrumental)']],
            ['title' => 'Starry Smile Story', 'date' => '2026-03-22', 'download' => true, 'tracks' => ['Starry Smile Story']],
            ['title' => '霞日和／Starry Smile Story', 'date' => '2026-07-22', 'tracks' => ['霞日和', 'Starry Smile Story', 'Message Card', '霞日和 (Instrumental)', 'Starry Smile Story (Instrumental)', 'Message Card (Instrumental)']],
        ];
    }
}
