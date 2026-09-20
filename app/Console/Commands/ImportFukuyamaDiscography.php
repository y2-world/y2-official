<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

class ImportFukuyamaDiscography extends Command
{
    protected $signature = 'import:fukuyama-discography {--dry-run : Show matching without saving}';

    protected $description = 'Import 福山雅治 albums and singles, matching tracklist songs to existing DbSong records';

    private const ARTIST_ID = 5;

    private function normalize(string $title): string
    {
        $title = mb_convert_kana($title, 'as');
        $title = str_replace(['’', '‘'], "'", $title);
        // ｢…｣｢･･･｣｢・・・｣など、中点/ドットが2つ以上連続するものは全て省略記号として統一する
        $title = preg_replace('/[･・.]{2,}|…+/u', '…', $title);
        $title = preg_replace('/\s+/u', '', $title);
        $title = mb_strtolower($title, 'UTF-8');
        return $title;
    }

    // 表記が全く異なるため自動マッチングできない曲の手動対応表（例: 中国語版タイトル → 基本曲名）
    private const MANUAL_TITLE_ALIASES = [
        '破曉' => '暁',
        'ON AND ON 09' => 'ON AND ON',
        '蜜柑色の夏休み2015' => '蜜柑色の夏休み',
    ];

    // 曲titleに対して、[DbSong.id, 基本形と表記が異なる場合はその原文表記(exception用)] を返す
    private function findSong(array $songsByNormalizedTitle, array $songTitlesById, string $title): ?array
    {
        if (isset(self::MANUAL_TITLE_ALIASES[$title])) {
            $baseTitle = self::MANUAL_TITLE_ALIASES[$title];
            $normalizedBase = $this->normalize($baseTitle);
            if (isset($songsByNormalizedTitle[$normalizedBase])) {
                return ['id' => $songsByNormalizedTitle[$normalizedBase], 'exception' => $title];
            }
        }

        $normalized = $this->normalize($title);
        if (isset($songsByNormalizedTitle[$normalized])) {
            $id = $songsByNormalizedTitle[$normalized];
            $baseTitle = $songTitlesById[$id];
            return ['id' => $id, 'exception' => ($baseTitle === $title) ? null : $title];
        }

        // バージョン違い表記（例: "Good night (remix)"）は括弧以降を除いた基本形で再検索
        $stripped = preg_replace('/[\(（].*$/u', '', $title);
        $strippedNormalized = $this->normalize($stripped);
        if ($strippedNormalized !== $normalized && isset($songsByNormalizedTitle[$strippedNormalized])) {
            $id = $songsByNormalizedTitle[$strippedNormalized];
            return ['id' => $id, 'exception' => $title];
        }

        return null;
    }

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');

        // 過去の実行で全角スラッシュ表記だった頃に作られた重複レコードを削除する
        $staleSingles = DbSingle::where('artist_id', self::ARTIST_ID)->where('title', 'like', '%／%')->get(['id', 'title']);
        foreach ($staleSingles as $stale) {
            $this->warn("Removing stale duplicate single: id={$stale->id} title={$stale->title}");
            if (!$dryRun) {
                $stale->delete();
            }
        }

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

                $match = $this->findSong($songsByNormalizedTitle, $songTitlesById, $trackTitle);
                if ($match === null) {
                    $unmatched[] = $albumData['title'] . ' / ' . $trackTitle;
                    continue;
                }
                $track = ['id' => $match['id']];
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
                DbAlbum::updateOrCreate(
                    ['artist_id' => self::ARTIST_ID, 'title' => $albumData['title'], 'date' => $albumData['date']],
                    [
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
                $track = ['id' => $match['id']];
                if ($match['exception']) {
                    $track['exception'] = $match['exception'];
                }
                $trackIds[] = $track;
            }

            $isCd = empty($singleData['download']);
            if ($isCd) {
                $singleId++;
            }

            $this->line("=== [single] {$singleData['title']} ({$singleData['date']}) === matched " . count($trackIds) . '/' . count($singleData['tracks']));

            if (!$dryRun) {
                DbSingle::updateOrCreate(
                    ['artist_id' => self::ARTIST_ID, 'title' => $singleData['title'], 'date' => $singleData['date']],
                    [
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
                'title' => '伝言',
                'date' => '1990-04-21',
                'tracks' => ['PEACE IN THE PARK', 'DEAD BODY', 'まぼろし', 'BLUE SMOKY', '古い友への手紙', '追憶の雨の中', 'かなしみは…', 'WHITE LIGHT WHITE HEAT', 'WIND FROM JUNGLE', 'I LOVE YOU'],
            ],
            [
                'title' => 'LION',
                'date' => '1991-03-21',
                'tracks' => ['もう君はいない', 'このままRain', '風をさがしてる', '雨のメインストリート', '逃げられない', 'LOVE SONG', 'アクセス', 'どうしたらいいんだろう', 'Radio Days 〜1943…〜', 'ザラついたシチュエイション', 'この国を', 'もっとそばにきて'],
            ],
            [
                'title' => 'BROS.',
                'date' => '1991-11-06',
                'tracks' => ['太陽はふたつない', '天使の翼にくちづけを', 'WOH WOW', 'ただ僕がかわった', '1991年のクリスマスソング', 'Running Through The Dark', '00:20 AM', 'Tomato Game', 'Jの店', '天国の扉を開けて欲しい夜', '見えない明日'],
            ],
            [
                'title' => 'BOOTS',
                'date' => '1992-11-21',
                'tracks' => ['スタート', 'Girl', '約束の丘', 'Cool', 'ふたつの鼓動', 'HARD RAIN', '恋', 'Hold on Me', 'Good night', 'みつめていたい'],
            ],
            [
                'title' => 'Calling',
                'date' => '1993-10-21',
                'tracks' => ['Calling', 'All My Loving', 'Moon', '言い出せなくて…', 'KISS AND KILL ME', '恋人', '遠くへ', 'MELODY', "Marcy's Song", 'IN THE CITY', 'IN MY HEART', 'Good Luck', 'SORRY BABY'],
            ],
            [
                'title' => 'ON AND ON',
                'date' => '1994-06-09',
                'tracks' => ['ON AND ON', "IT'S ONLY LOVE", 'BLOOD', '1985年 Factory Street 夏', '熱いくちづけ', '雨を聴きながら', 'Dear', 'ダンスしないか', '明日へのマーチ', 'GLOAMING WAY', 'ぼくの朝'],
            ],
            [
                'title' => 'SING A SONG',
                'date' => '1998-06-24',
                'tracks' => ['愛は風のように', 'Heart', 'Good Job', 'you', '僕らの愛は今日も忙しい', 'You Can Dance', '遠い旅', 'Hard Luck Lover', '80 Proof', '巻き戻した夏', 'Like A Hurricane', 'Fellow'],
            ],
            [
                'title' => 'f',
                'date' => '2001-04-25',
                'tracks' => ['友よ', 'HEAVEN', 'Venus', '蜜柑色の夏休み', '桜坂', 'Escape', 'HEY!', 'Gang★', 'dogi-magi', 'Blues', 'Carnival', '家路', '春夏秋冬'],
            ],
            [
                'title' => '5年モノ',
                'date' => '2006-12-06',
                'tracks' => ['FREEDOM', 'THE EDGE OF CHAOS 〜愛の一撃〜', '虹', 'ひまわり', 'それがすべてさ', '泣いたりしないで', 'RED×BLUE', '東京', 'milk tea', '美しき花', 'LOVE TRAIN', 'あの夏も 海も 空も', 'BEAUTIFUL DAY', 'わたしは風になる'],
            ],
            [
                'title' => '残響',
                'date' => '2009-06-30',
                'tracks' => ['群青 〜ultramarine〜', '化身', '明日の☆SHOW', 'ながれ星', '幸福論', '18 〜eighteen〜', '最愛', '想 -new love new world-', 'phantom', 'survivor', '今夜、君を抱いて', '旅人', '東京にもあったんだ', '道標'],
            ],
            [
                'title' => 'HUMAN',
                'date' => '2014-04-02',
                'tracks' => [
                    ['クスノキ', 'Disc 1'], ['Prelude', 'Disc 1'], ['HUMAN', 'Disc 1'], ['とりビー!', 'Disc 1'], ['ミスキャスト', 'Disc 1'], ['246', 'Disc 1'], ['Cherry', 'Disc 1'], ['暁', 'Disc 1'], ['昭和やったね', 'Disc 1'],
                    ['家族になろうよ', 'Disc 2'], ['fighting pose', 'Disc 2'], ['生きてる生きてく', 'Disc 2'], ['Around the world', 'Disc 2'], ['Beautiful life', 'Disc 2'], ['GAME', 'Disc 2'], ['誕生日には真白な百合を', 'Disc 2'], ['Get the groove', 'Disc 2'], ['恋の魔力', 'Disc 2'],
                ],
            ],
            [
                'title' => 'AKIRA',
                'date' => '2020-12-08',
                'tracks' => ['AKIRA', '暗闇の中で飛べ', '革命', 'Popstar', '漂流せよ', 'トモエ学園', '失敗学', '甲子園', 'ボーッ', '心音', '幸せのサラダ', '1461日', '聖域', 'いってらっしゃい', '零 -ZERO-', '始まりがまた始まってゆく', '彼方で'],
            ],
            [
                'title' => '超新星',
                'date' => '2026-09-09',
                'tracks' => ['SUPERNOVA', 'ウシュクベーハー', '邂逅', '拍手喝采', '木星 feat. 稲葉浩志', '龍', '幻界', '未来絵', 'クスノキ', 'Great Freedom', '万有引力', 'ひとみ', '想望', 'Walking with you', '光', '妖', 'ヒトツボシ'],
            ],
            // ベストアルバム
            [
                'title' => 'M-COLLECTION 風をさがしてる',
                'date' => '1995-06-09',
                'best' => true,
                'tracks' => [
                    ['追憶の雨の中', 'Disc-1'], ['かなしみは…', 'Disc-1'], ['アクセス', 'Disc-1'], ['Radio Days 〜1943…〜', 'Disc-1'], ['風をさがしてる', 'Disc-1'], ['逃げられない', 'Disc-1'], ['WOH WOW', 'Disc-1'], ['ただ僕がかわった', 'Disc-1'], ['Good night', 'Disc-1'], ['ひとりきり歩いてく帰り道で', 'Disc-1'],
                    ['約束の丘', 'Disc-2'], ['ふたつの鼓動', 'Disc-2'], ['MELODY', 'Disc-2'], ['BABY BABY', 'Disc-2'], ['All My Loving', 'Disc-2'], ['恋人', 'Disc-2'], ["IT'S ONLY LOVE", 'Disc-2'], ['SORRY BABY', 'Disc-2'], ['HELLO', 'Disc-2'], ['そのままで…', 'Disc-2'], ['Pa Pa Pa', 'Disc-2'], ["IT'S ONLY LOVE (Strings Version)", 'Disc-2'],
                ],
            ],
            [
                'title' => 'MAGNUM COLLECTION 1999 "Dear"',
                'date' => '1999-12-08',
                'best' => true,
                'tracks' => [
                    ['追憶の雨の中 (remix)', 'Disc-1'], ['風をさがしてる (TV Special/95 style)', 'Disc-1'], ['ただ僕がかわった (remix)', 'Disc-1'], ['Good night (remix)', 'Disc-1'], ['約束の丘 (remix)', 'Disc-1'], ['MELODY (remix)', 'Disc-1'], ['恋人 (remix)', 'Disc-1'], ['遠くへ (remix)', 'Disc-1'], ["Marcy's Song (remix)", 'Disc-1'], ["IT'S ONLY LOVE (remix)", 'Disc-1'], ['1985年 Factory Street 夏 (remix)', 'Disc-1'], ['GLOAMING WAY (remix)', 'Disc-1'], ['明日へのマーチ (remix)', 'Disc-1'], ['Dear (remix)', 'Disc-1'],
                    ['HELLO', 'Disc-2'], ['Message', 'Disc-2'], ['今 このひとときが 遠い夢のように', 'Disc-2'], ['Heart', 'Disc-2'], ['you', 'Disc-2'], ['Like A Hurricane', 'Disc-2'], ['巻き戻した夏', 'Disc-2'], ['Peach!!', 'Disc-2'], ['Squall', 'Disc-2'], ['DEAD BODY (Live/95 Style)', 'Disc-2'], ['BLOOD (Live/95 Style)', 'Disc-2'], ['Good Luck (Live/95 Style)', 'Disc-2'], ['SORRY BABY (Live/98 Style)', 'Disc-2'], ['もっとそばにきて (Santa Monica Blvd./99 Style)', 'Disc-2'],
                ],
            ],
            [
                'title' => 'MAGNUM COLLECTION "SLOW"',
                'date' => '2003-08-27',
                'best' => true,
                'tracks' => ['Good night', 'Girl', '雨のメインストリート', 'Hold on Me', '恋人', "IT'S ONLY LOVE", 'Good Luck', 'GLOAMING WAY', 'Dear', 'ぼくの朝', 'そのままで…', 'you', '遠い旅', '巻き戻した夏', 'Squall'],
            ],
            [
                'title' => 'THE BEST BANG!!',
                'date' => '2010-11-17',
                'best' => true,
                'tracks' => [
                    ['追憶の雨の中', 'Disc 1'], ['逃げられない', 'Disc 1'], ['約束の丘', 'Disc 1'], ['HARD RAIN', 'Disc 1'], ['Good night', 'Disc 1'], ['MELODY', 'Disc 1'], ['All My Loving', 'Disc 1'], ['遠くへ', 'Disc 1'], ['恋人', 'Disc 1'], ["Marcy's Song", 'Disc 1'], ["IT'S ONLY LOVE", 'Disc 1'], ['HELLO', 'Disc 1'], ['Good Luck', 'Disc 1'], ['Message', 'Disc 1'], ['Heart', 'Disc 1'], ['you', 'Disc 1'],
                    ['HEAVEN', 'Disc 2'], ['Peach!!', 'Disc 2'], ['Squall', 'Disc 2'], ['Gang★', 'Disc 2'], ['桜坂', 'Disc 2'], ['蜜柑色の夏休み', 'Disc 2'], ['虹', 'Disc 2'], ['ひまわり', 'Disc 2'], ['それがすべてさ', 'Disc 2'], ['泣いたりしないで', 'Disc 2'], ['RED×BLUE', 'Disc 2'], ['あの夏も 海も 空も', 'Disc 2'], ['milk tea', 'Disc 2'], ['東京にもあったんだ', 'Disc 2'],
                    ['THE EDGE OF CHAOS 〜愛の一撃〜', 'Disc 3'], ['明日の☆SHOW', 'Disc 3'], ['最愛', 'Disc 3'], ['想 -new love new world-', 'Disc 3'], ['化身', 'Disc 3'], ['はつ恋', 'Disc 3'], ['KISSして', 'Disc 3'], ['少年', 'Disc 3'], ['蛍', 'Disc 3'], ['群青 〜ultramarine〜', 'Disc 3'], ['vs. 〜知覚と快楽の螺旋〜', 'Disc 3'], ['覚醒モーメント', 'Disc 3'], ['でんでらりゅうば', 'Disc 3'], ['99', 'Disc 3'], ['Revolution//Evolution', 'Disc 3'], ['アンモナイトの夢', 'Disc 3'],
                    ['心color 〜a song for the wonderful year〜', 'Disc 4'], ['石塊のプライド', 'Disc 4'], ['道標 (2010)', 'Disc 4'],
                ],
            ],
            [
                'title' => '福の音',
                'date' => '2015-12-23',
                'best' => true,
                'tracks' => [
                    ['I am a HERO', 'Reel.1'], ['何度でも花が咲くように私を生きよう', 'Reel.1'], ['クスノキ', 'Reel.1'], ['Prelude', 'Reel.1'], ['HUMAN', 'Reel.1'], ['暁', 'Reel.1'], ['Get the groove', 'Reel.1'], ['誕生日には真白な百合を', 'Reel.1'], ['GAME', 'Reel.1'], ['Beautiful life', 'Reel.1'], ['生きてる生きてく', 'Reel.1'], ['家族になろうよ', 'Reel.1'], ['fighting pose', 'Reel.1'], ['vs. 〜知覚と快楽の螺旋〜 (2013)', 'Reel.1'], ['蛍', 'Reel.1'], ['少年', 'Reel.1'], ['破曉', 'Reel.1'],
                    ['Revolution//Evolution', 'Reel.2'], ['はつ恋', 'Reel.2'], ['18 〜eighteen〜', 'Reel.2'], ['ながれ星', 'Reel.2'], ['幸福論', 'Reel.2'], ['最愛', 'Reel.2'], ['KISSして', 'Reel.2'], ['化身', 'Reel.2'], ['道標', 'Reel.2'], ['明日の☆SHOW', 'Reel.2'], ['想 -new love new world-', 'Reel.2'], ['東京にもあったんだ', 'Reel.2'], ['BEAUTIFUL DAY', 'Reel.2'], ['milk tea', 'Reel.2'], ['あの夏も 海も 空も', 'Reel.2'],
                    ['東京', 'Reel.3'], ['虹', 'Reel.3'], ['ひまわり', 'Reel.3'], ['それがすべてさ', 'Reel.3'], ['Gang★', 'Reel.3'], ['HEY!', 'Reel.3'], ['桜坂', 'Reel.3'], ['HELLO', 'Reel.3'], ["IT'S ONLY LOVE", 'Reel.3'], ['Squall (Live)', 'Reel.3'], ['恋人 (Live)', 'Reel.3'], ['Good night (Live)', 'Reel.3'], ['Good Luck (Live)', 'Reel.3'], ['追憶の雨の中 (Live)', 'Reel.3'],
                ],
            ],
        ];
    }

    private function singlesData(): array
    {
        return [
            ['title' => '追憶の雨の中', 'date' => '1990-03-21', 'tracks' => ['追憶の雨の中', 'かなしみは…']],
            ['title' => 'アクセス', 'date' => '1990-11-07', 'tracks' => ['アクセス', 'Radio Days 〜1943…〜']],
            ['title' => '風をさがしてる', 'date' => '1991-02-21', 'tracks' => ['風をさがしてる', '逃げられない']],
            ['title' => 'WOH WOW / ただ僕がかわった', 'date' => '1991-10-21', 'tracks' => ['WOH WOW', 'ただ僕がかわった']],
            ['title' => 'Good night', 'date' => '1992-05-21', 'tracks' => ['Good night', 'ひとりきり歩いてく帰り道で']],
            ['title' => '約束の丘', 'date' => '1992-10-28', 'tracks' => ['約束の丘', 'ふたつの鼓動']],
            ['title' => 'MELODY / BABY BABY', 'date' => '1993-06-02', 'tracks' => ['MELODY', 'BABY BABY']],
            ['title' => 'All My Loving / 恋人', 'date' => '1993-09-29', 'tracks' => ['All My Loving', '恋人', 'All My Loving (Original Karaoke)', '恋人 (Original Karaoke)']],
            ['title' => "IT'S ONLY LOVE/SORRY BABY", 'date' => '1994-03-24', 'tracks' => ["IT'S ONLY LOVE", 'SORRY BABY']],
            ['title' => 'HELLO', 'date' => '1995-02-06', 'tracks' => ['HELLO', 'そのままで…', 'Pa Pa Pa']],
            ['title' => 'Message / 今 このひとときが 遠い夢のように', 'date' => '1995-10-02', 'tracks' => ['Message', '今 このひとときが 遠い夢のように']],
            ['title' => 'Heart / you', 'date' => '1998-04-30', 'tracks' => ['Heart', 'you', 'Like A Hurricane']],
            ['title' => 'Peach!! / Heart of Xmas', 'date' => '1998-11-05', 'tracks' => ['Peach!!', 'Heart of Xmas']],
            ['title' => 'HEAVEN / Squall', 'date' => '1999-11-17', 'tracks' => ['HEAVEN', 'Squall']],
            ['title' => '桜坂', 'date' => '2000-04-26', 'tracks' => ['桜坂', '春夏秋冬']],
            ['title' => 'HEY!', 'date' => '2000-10-12', 'tracks' => ['HEY!', '家路', 'HEY! (The victory run)', '家路 (Putting on the laurel crown)']],
            ['title' => 'Gang★', 'date' => '2001-03-28', 'tracks' => ['Gang★', 'Sweet Darling']],
            ['title' => '虹 / ひまわり / それがすべてさ', 'date' => '2003-08-27', 'tracks' => ['虹', 'ひまわり', 'それがすべてさ', 'ひまわり (fields of toscana)']],
            ['title' => '泣いたりしないで / RED×BLUE', 'date' => '2004-12-01', 'tracks' => ['泣いたりしないで', 'RED×BLUE']],
            ['title' => '東京', 'date' => '2005-08-17', 'tracks' => ['東京', 'わたしは風になる']],
            ['title' => 'milk tea / 美しき花', 'date' => '2006-05-24', 'tracks' => ['milk tea', '美しき花', 'LOVE TRAIN', 'あの夏も 海も 空も']],
            ['title' => '東京にもあったんだ / 無敵のキミ', 'date' => '2007-04-11', 'tracks' => ['東京にもあったんだ', '無敵のキミ']],
            ['title' => '想 -new love new world-', 'date' => '2008-10-22', 'tracks' => ['想 -new love new world-']],
            ['title' => '化身', 'date' => '2009-05-20', 'tracks' => ['化身', '道標', '追憶の雨の中 (Live)']],
            ['title' => 'はつ恋', 'date' => '2009-12-16', 'tracks' => ['はつ恋', 'ON AND ON 09', 'アンモナイトの夢']],
            ['title' => '蛍 / 少年', 'date' => '2010-08-11', 'tracks' => ['蛍', '少年', 'Revolution//Evolution']],
            ['title' => '家族になろうよ / fighting pose', 'date' => '2011-08-31', 'tracks' => ['家族になろうよ', 'fighting pose', 'HARD RAIN (Live)', '家族になろうよ (Wedding Ver.)']],
            ['title' => '生きてる生きてく', 'date' => '2012-03-28', 'tracks' => ['生きてる生きてく', 'Around the world', 'Dear (Live)', '逃げられない (Live)']],
            ['title' => 'Beautiful life / GAME', 'date' => '2012-10-10', 'tracks' => ['Beautiful life', 'GAME', 'Girl (2012)', 'Beautiful life (Sing with the piano ver.)']],
            ['title' => '誕生日には真白な百合を / Get the groove', 'date' => '2013-04-10', 'tracks' => ['誕生日には真白な百合を', 'Get the groove', '愛は風のように (Live)', 'Good Job (Live)']],
            ['title' => 'I am a HERO', 'date' => '2015-08-19', 'tracks' => ['I am a HERO', 'ステージの魔物', 'その笑顔が見たい', '何度でも花が咲くように私を生きよう', '蜜柑色の夏休み2015', 'I LOVE YOU (BROS.盤)']],
            ['title' => '聖域', 'date' => '2017-09-13', 'tracks' => ['聖域', 'jazzとHepburnと君と', 'Humbucker vs. Single-Coil', '聖域 (弾き語り)', 'jazzとHepburnと君と (弾き語り)']],
            // デジタルシングル
            ['title' => '何度でも花が咲くように私を生きよう', 'date' => '2015-03-25', 'download' => true, 'tracks' => ['何度でも花が咲くように私を生きよう']],
            ['title' => '1461日', 'date' => '2016-08-05', 'download' => true, 'tracks' => ['1461日']],
            ['title' => 'トモエ学園', 'date' => '2017-12-01', 'download' => true, 'tracks' => ['トモエ学園']],
            ['title' => '零 -ZERO-', 'date' => '2018-04-07', 'download' => true, 'tracks' => ['零 -ZERO-']],
            ['title' => '甲子園', 'date' => '2018-08-27', 'download' => true, 'tracks' => ['甲子園']],
            ['title' => '心音', 'date' => '2020-11-09', 'download' => true, 'tracks' => ['心音']],
            ['title' => '道標 2022', 'date' => '2022-02-06', 'download' => true, 'tracks' => ['道標']],
            ['title' => '妖', 'date' => '2022-12-05', 'download' => true, 'tracks' => ['妖']],
            ['title' => '想望', 'date' => '2023-12-04', 'download' => true, 'tracks' => ['想望']],
            ['title' => 'ひとみ', 'date' => '2024-02-19', 'download' => true, 'tracks' => ['ひとみ']],
            ['title' => 'クスノキ', 'date' => '2025-06-30', 'download' => true, 'tracks' => ['クスノキ']],
            ['title' => '幻界', 'date' => '2025-09-08', 'download' => true, 'tracks' => ['幻界']],
            ['title' => '万有引力', 'date' => '2025-09-25', 'download' => true, 'tracks' => ['万有引力']],
            ['title' => '龍', 'date' => '2025-11-29', 'download' => true, 'tracks' => ['龍']],
            ['title' => '木星 feat. 稲葉浩志', 'date' => '2025-12-24', 'download' => true, 'tracks' => ['木星 feat. 稲葉浩志']],
            ['title' => '邂逅', 'date' => '2026-08-10', 'download' => true, 'tracks' => ['邂逅']],
        ];
    }
}
