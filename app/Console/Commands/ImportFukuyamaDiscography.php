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

    private function findSongId(array $songsByNormalizedTitle, string $title): ?int
    {
        $normalized = $this->normalize($title);
        if (isset($songsByNormalizedTitle[$normalized])) {
            return $songsByNormalizedTitle[$normalized];
        }

        // バージョン違い表記（例: "Good night (remix)"）は括弧以降を除いた基本形で再検索
        $stripped = preg_replace('/[\(（].*$/u', '', $title);
        $strippedNormalized = $this->normalize($stripped);
        if ($strippedNormalized !== $normalized && isset($songsByNormalizedTitle[$strippedNormalized])) {
            return $songsByNormalizedTitle[$strippedNormalized];
        }

        return null;
    }

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');

        $songs = DbSong::where('artist_id', self::ARTIST_ID)->get(['id', 'title']);
        $songsByNormalizedTitle = [];
        foreach ($songs as $s) {
            $songsByNormalizedTitle[$this->normalize($s->title)] = $s->id;
        }

        $albums = $this->albumsData();
        $unmatched = [];

        foreach ($albums as $albumData) {
            $trackIds = [];
            foreach ($albumData['tracks'] as $trackTitle) {
                $id = $this->findSongId($songsByNormalizedTitle, $trackTitle);
                if ($id === null) {
                    $unmatched[] = $albumData['title'] . ' / ' . $trackTitle;
                    continue;
                }
                $trackIds[] = ['id' => $id];
            }

            $this->line("=== {$albumData['title']} ({$albumData['date']}) === matched " . count($trackIds) . '/' . count($albumData['tracks']));

            if (!$dryRun) {
                DbAlbum::updateOrCreate(
                    ['artist_id' => self::ARTIST_ID, 'title' => $albumData['title'], 'date' => $albumData['date']],
                    [
                        'best' => $albumData['best'] ?? false,
                        'mini' => $albumData['mini'] ?? false,
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
                'tracks' => ['クスノキ', 'Prelude', 'HUMAN', 'とりビー!', 'ミスキャスト', '246', 'Cherry', '暁', '昭和やったね', '家族になろうよ', 'fighting pose', '生きてる生きてく', 'Around the world', 'Beautiful life', 'GAME', '誕生日には真白な百合を', 'Get the groove', '恋の魔力'],
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
                'tracks' => ['追憶の雨の中', 'かなしみは…', 'アクセス', 'Radio Days 〜1943…〜', '風をさがしてる', '逃げられない', 'WOH WOW', 'ただ僕がかわった', 'Good night', 'ひとりきり歩いてく帰り道で', '約束の丘', 'ふたつの鼓動', 'MELODY', 'BABY BABY', 'All My Loving', '恋人', "IT'S ONLY LOVE", 'SORRY BABY', 'HELLO', 'そのままで…', 'Pa Pa Pa'],
            ],
            [
                'title' => 'MAGNUM COLLECTION 1999 "Dear"',
                'date' => '1999-12-08',
                'best' => true,
                'tracks' => ['追憶の雨の中', '風をさがしてる', 'ただ僕がかわった', 'Good night', '約束の丘', 'MELODY', '恋人', '遠くへ', "Marcy's Song", "IT'S ONLY LOVE", '1985年 Factory Street 夏', 'GLOAMING WAY', '明日へのマーチ', 'Dear', 'HELLO', 'Message', '今 このひとときが 遠い夢のように', 'Heart', 'you', 'Like A Hurricane', '巻き戻した夏', 'Peach!!', 'Squall', 'DEAD BODY', 'BLOOD', 'Good Luck', 'SORRY BABY', 'もっとそばにきて'],
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
                'tracks' => ['追憶の雨の中', '逃げられない', '約束の丘', 'HARD RAIN', 'Good night', 'MELODY', 'All My Loving', '遠くへ', '恋人', "Marcy's Song", "IT'S ONLY LOVE", 'HELLO', 'Good Luck', 'Message', 'Heart', 'you', 'HEAVEN', 'Peach!!', 'Squall', 'Gang★', '桜坂', '蜜柑色の夏休み', '虹', 'ひまわり', 'それがすべてさ', '泣いたりしないで', 'RED×BLUE', 'あの夏も 海も 空も', 'milk tea', '東京にもあったんだ', 'THE EDGE OF CHAOS 〜愛の一撃〜', '明日の☆SHOW', '最愛', '想 -new love new world-', '化身', 'はつ恋', 'KISSして', '少年', '蛍', '群青 〜ultramarine〜', 'vs. 〜知覚と快楽の螺旋〜', '覚醒モーメント', 'でんでらりゅうば', '99', 'Revolution//Evolution', 'アンモナイトの夢', '心color 〜a song for the wonderful year〜', '石塊のプライド', '道標'],
            ],
            [
                'title' => '福の音',
                'date' => '2015-12-23',
                'best' => true,
                'tracks' => ['I am a HERO', '何度でも花が咲くように私を生きよう', 'クスノキ', 'Prelude', 'HUMAN', '暁', 'Get the groove', '誕生日には真白な百合を', 'GAME', 'Beautiful life', '生きてる生きてく', '家族になろうよ', 'fighting pose', 'vs. 〜知覚と快楽の螺旋〜', '蛍', '少年', 'Revolution//Evolution', 'はつ恋', '18 〜eighteen〜', 'ながれ星', '幸福論', '最愛', 'KISSして', '化身', '道標', '明日の☆SHOW', '想 -new love new world-', '東京にもあったんだ', 'BEAUTIFUL DAY', 'milk tea', 'あの夏も 海も 空も', '東京', '虹', 'ひまわり', 'それがすべてさ', 'Gang★', 'HEY!', '桜坂', 'HELLO', "IT'S ONLY LOVE", 'Squall', '恋人', 'Good night', 'Good Luck', '追憶の雨の中'],
            ],
            // ミニアルバム
            [
                'title' => 'ヒトツボシ 〜ガリレオ Collection 2007-2022〜',
                'date' => '2022-09-14',
                'mini' => true,
                'tracks' => ['ヒトツボシ', 'KISSして', '最愛', '恋の魔力', '99', 'ヒトツボシ'],
            ],
        ];
    }
}
