<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

class ImportSukimaswitchDiscography extends Command
{
    protected $signature = 'import:sukimaswitch-discography {--dry-run : Show matching without saving}';

    protected $description = 'Import スキマスイッチ albums and singles, matching tracklist songs to existing DbSong records';

    private const ARTIST_ID = 10;

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

    // 表記が全く異なるため自動マッチングできない曲の手動対応表（キーは前方一致で判定）
    private const MANUAL_TITLE_ALIASES = [
        'I-T-A-Z-U-R-A' => 'I-TA-ZU-RA',
    ];

    private function findSong(array $songsByNormalizedTitle, array $songTitlesById, string $title): ?array
    {
        foreach (self::MANUAL_TITLE_ALIASES as $aliasFrom => $baseTitle) {
            if (!str_starts_with($title, $aliasFrom)) {
                continue;
            }
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
            if ($rest !== '' && !preg_match('/^[\s\(（\-〜～<]/u', $rest)) {
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
                $trackTitle = is_array($trackEntry) ? $trackEntry[0] : $trackEntry;
                $disc = is_array($trackEntry) ? $trackEntry[1] : null;

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
                'title' => '夏雲ノイズ',
                'date' => '2004-06-23',
                'tracks' => ['螺旋（らせん）', 'ふれて未来を', '桜夜風', 'view', 'きみがいいなら', 'ドーシタトースター', '君の話 〜エヴォリューションMix〜', '僕の話', '種を蒔く人', 'キミドリ色の世界', 'えんぴつケシゴム 〜overture〜', '奏（かなで）'],
            ],
            [
                'title' => '空創クリップ',
                'date' => '2005-07-20',
                'tracks' => ['君に告げる', '全力少年', '水色のスカート', '冬の口笛 (feat. Takuya ver.)', 'フィクション', 'さみしくとも明日を待つ（Album ver.）', 'キレイだ', 'かけら ほのか', '目が覚めて', '飲みに来ないか', '雨待ち風（Album ver.）'],
            ],
            [
                'title' => '夕風ブレンド',
                'date' => '2006-11-29',
                'tracks' => ['藍', 'ガラナ（album ver.）', 'スフィアの羽根（album ver.）', '惑星タイマー（album ver.）', '月見ヶ丘', '空創トリップ', 'ボクノート', 'ズラチナルーカ', '糸ノ意図', 'アカツキの詩（album ver.）', 'アーセンの憂鬱', '願い言', '1+1'],
            ],
            [
                'title' => 'ナユタとフカシギ',
                'date' => '2009-11-04',
                'tracks' => ['双星プロローグ', '雫', 'ゴールデンタイムラバー（album ver.）', 'ムーンライトで行こう', '病院にいく', 'デザイナーズマンション', '8ミリメートル', 'レモネード', 'SL9', '虹のレシピ（album ver.）', '光る'],
            ],
            [
                'title' => 'musium',
                'date' => '2011-10-05',
                'tracks' => ['時間の止め方', 'アイスクリーム シンドローム（album ver.）', '石コロDays', 'LとR', 'ソングライアー', 'センチメンタル ホームタウン', 'さいごのひ (album ver.)', 'Andersen', 'スモーキンレイニーブルー', '晴ときどき曇', 'またね。'],
            ],
            [
                'title' => 'スキマスイッチ',
                'date' => '2014-12-03',
                'tracks' => ['ゲノム', 'パラボラヴァ', '僕と傘と日曜日', 'life×life×life', 'Ah Yeah!!', '夏のコスモナウト（album ver.）', '蝶々ノコナ', '思い出クロール', '星のうつわ（album ver.）', 'SF'],
            ],
            [
                'title' => '新空間アルゴリズム',
                'date' => '2018-03-14',
                'tracks' => ['リチェルカ', 'LINE (shinku-kan mix)', 'パーリー！パーリー！', 'ミスランドリー', 'Revival', '未来花', 'ミスターカイト (shinku-kan mix)', 'Baby good sleep', 'さよならエスケープ', 'リアライズ'],
            ],
            [
                'title' => 'Hot Milk',
                'date' => '2021-11-24',
                'tracks' => ['OverDriver', '吠えろ!', '青春', 'Ordinary', '東京', 'スイッチ!', 'されど愛しき人生'],
            ],
            [
                'title' => 'Bitter Coffee',
                'date' => '2021-11-24',
                'tracks' => ['I-T-A-Z-U-R-A', 'G.A.M.E.', '風がめくるページ', 'いろは', 'SINK', 'フォークで恋して', 'あけたら'],
            ],
            [
                'title' => 'A museMentally',
                'date' => '2024-07-10',
                'tracks' => ['Intro ～for Compact Disc～', 'ゼログラ', "Lovin' Song", '逆転トリガー', 'ごめんねベイビー', '遠くでサイレンが泣く', 'Lonelyの事情', 'コトバリズム', '魔法がかかった日', 'クライマル', '君と願いを'],
            ],
            // ベストアルバム
            [
                'title' => 'グレイテスト・ヒッツ',
                'date' => '2007-08-01',
                'best' => true,
                'tracks' => ['view', '君の話', '奏（かなで）', 'ふれて未来を', '冬の口笛', '全力少年', '雨待ち風', 'キレイだ', '飲みに来ないか', 'ボクノート', 'ガラナ', 'スフィアの羽根', 'アカツキの詩', '惑星タイマー', 'マリンスノウ'],
            ],
            [
                'title' => 'DOUBLES BEST',
                'date' => '2012-08-22',
                'best' => true,
                'tracks' => ['全力少年', 'アイスクリーム シンドローム', 'ふれて未来を', '藍', '螺旋（らせん）', 'ボクノート', '雫', '晴ときどき曇', 'view', 'ガラナ', '奏（かなで）', 'ただそれだけの風景', 'ラストシーン', 'ユリーカ'],
            ],
            [
                'title' => "POPMAN'S WORLD〜All Time Best 2003-2013〜",
                'date' => '2013-08-21',
                'best' => true,
                'tracks' => [
                    ['view', 'DISC-1'], ['君の話', 'DISC-1'], ['奏（かなで）', 'DISC-1'], ['ふれて未来を', 'DISC-1'], ['冬の口笛', 'DISC-1'], ['全力少年', 'DISC-1'], ['雨待ち風', 'DISC-1'], ['キレイだ', 'DISC-1'], ['飲みに来ないか', 'DISC-1'], ['ボクノート', 'DISC-1'], ['ガラナ', 'DISC-1'], ['スフィアの羽根', 'DISC-1'], ['アカツキの詩', 'DISC-1'], ['藍', 'DISC-1'], ['惑星タイマー', 'DISC-1'], ['マリンスノウ', 'DISC-1'],
                    ['虹のレシピ', 'DISC-2'], ['雫', 'DISC-2'], ['ゴールデンタイムラバー', 'DISC-2'], ['8ミリメートル', 'DISC-2'], ['アイスクリーム シンドローム', 'DISC-2'], ['さいごのひ', 'DISC-2'], ['晴ときどき曇', 'DISC-2'], ['石コロDays', 'DISC-2'], ['センチメンタル ホームタウン', 'DISC-2'], ['ラストシーン', 'DISC-2'], ['ユリーカ', 'DISC-2'], ['スカーレット', 'DISC-2'], ['トラベラーズ・ハイ', 'DISC-2'], ['Hello Especially', 'DISC-2'],
                ],
            ],
            [
                'title' => "POPMAN'S ANOTHER WORLD",
                'date' => '2016-04-13',
                'best' => true,
                'tracks' => [
                    ['夕凪', 'DISC-1'], ['スフィアの羽根', 'DISC-1'], ['ためいき', 'DISC-1'], ['電話キ', 'DISC-1'], ['石コロDays', 'DISC-1'], ['Aアングル', 'DISC-1'], ['弦楽四重奏のための『ドーシタトースター』', 'DISC-1'], ['僕の話-プロトタイプ-', 'DISC-1'], ['快楽のソファー(仮）2014 ver.', 'DISC-1'], ['夏のコスモナウト', 'DISC-1'], ['サウンドオブ', 'DISC-1'], ['雨は止まない', 'DISC-1'], ['小さな手', 'DISC-1'],
                    ['雫', 'DISC-2'], ['ハナツ', 'DISC-2'], ['1017小節のラブソング', 'DISC-2'], ['僕の青い自転車', 'DISC-2'], ['青春騎士', 'DISC-2'], ['さみしくとも明日を待つ', 'DISC-2'], ['猫になれ', 'DISC-2'], ['君曜日', 'DISC-2'], ['Bアングル', 'DISC-2'], ['トラベラーズ・ハイ', 'DISC-2'], ['回想目盛', 'DISC-2'], ['またね。 (betsu-oke ver.)', 'DISC-2'], ['壊れかけのサイボーグ', 'DISC-2'], ['フレ! フレ!', 'DISC-2'],
                ],
            ],
            [
                'title' => "POPMAN'S WORLD -Second-",
                'date' => '2023-07-05',
                'best' => true,
                'tracks' => [
                    ['藍 ～僕たちの色彩～', 'Disc1'], ['Ah Yeah!!', 'Disc1'], ['パラボラヴァ', 'Disc1'], ['星のうつわ', 'Disc1'], ['LINE', 'Disc1'], ['ハナツ', 'Disc1'], ['奏（かなで） re:produced by スキマスイッチ', 'Disc1'], ['ミスターカイト', 'Disc1'], ['リチェルカ', 'Disc1'], ['未来花', 'Disc1'], ['Revival', 'Disc1'], ['青春', 'Disc1'], ['up!!!!!!', 'Disc1'], ['ボクノート ～for 20th Anniversary with Orchestra～', 'Disc1'], ['アニバースデー', 'Disc1'],
                    ['吠えろ!', 'Disc2'], ['アーセンの憂鬱', 'Disc2'], ['雫', 'Disc2'], ['僕と傘と日曜日', 'Disc2'], ['さみしくとも明日を待つ', 'Disc2'], ['ミスランドリー', 'Disc2'], ['ソングライアー', 'Disc2'], ['されど愛しき人生', 'Disc2'], ['東京', 'Disc2'], ['あけたら', 'Disc2'], ['飲みに来ないか', 'Disc2'], ['全力少年 produced by 奥田民生', 'Disc2'], ['双星プロローグ', 'Disc2'], ['SINK', 'Disc2'], ['ラストシーン', 'Disc2'],
                    ['OverDriver', 'Disc3'], ['トラベラーズ・ハイ', 'Disc3'], ['夏のコスモナウト', 'Disc3'], ['石コロDays', 'Disc3'], ['view〜オーヴァードライヴMIX〜', 'Disc3'], ['スモーキンレイニーブルー', 'Disc3'], ['ゲノム', 'Disc3'], ['電話キ', 'Disc3'], ['8ミリメートル', 'Disc3'], ['さいごのひ', 'Disc3'], ['アカツキの詩 (album ver.)', 'Disc3'], ['クリスマスがやってくる', 'Disc3'], ['スフィアの羽根', 'Disc3'], ['SL9', 'Disc3'], ['リアライズ', 'Disc3'],
                ],
            ],
            [
                'title' => 're:Action',
                'date' => '2017-02-15',
                'best' => true,
                'tracks' => ['全力少年 produced by 奥田民生', '僕と傘と日曜日 produced by 田島貴男(ORIGINAL LOVE)', 'フィクション produced by フラワーカンパニーズ', 'ユリーカ produced by GRAPEVINE', 'マリンスノウ produced by TRICERATOPS', 'Ah Yeah!! produced by 澤野弘之', '奏（かなで） re:produced by スキマスイッチ', '晴ときどき曇 produced by BENNY SINGS', '君のとなり produced by 小田和正', 'ふれて未来を produced by 真心ブラザーズ', 'ゴールデンタイムラバー produced by RHYMESTER', '冬の口笛 produced by SPECIAL OTHERS', '回奏パズル produced by KAN'],
            ],
            [
                'title' => 'スキマノハナタバ 〜Love Song Selection〜',
                'date' => '2018-09-19',
                'best' => true,
                'tracks' => ['パラボラヴァ', '奏（かなで）', '僕と傘と日曜日', '1017小節のラブソング', 'Revival', 'ボクノート', '小さな手', '藍', 'アイスクリーム シンドローム', 'ラストシーン', 'ただそれだけの風景', '未来花 for Anniversary', 'ありがとう re:produced by 常田真太郎'],
            ],
            [
                'title' => 'スキマノハナタバ 〜Smile Song Selection〜',
                'date' => '2020-08-19',
                'best' => true,
                'tracks' => ['全力少年 Remastered', '夏のコスモナウト', '青春', 'ガラナ', 'ミスターカイト', '星のうつわ', 'かけら ほのか', 'Hello Especially', 'LとR', 'トラベラーズ・ハイ', 'Ah Yeah!!', 'ゴールデンタイムラバー', '晴ときどき曇', 'あけたら'],
            ],
        ];
    }

    private function singlesData(): array
    {
        return [
            // ミニアルバム扱い→シングル区分（EP）
            ['title' => '君の話', 'date' => '2003-09-17', 'ep' => true, 'tracks' => ['君の話', '太陽', 'メロドラマ', '君のこと全部', 'view〜オーヴァードライヴMIX〜', 'ただそれだけの風景']],
            // 配信限定EP
            ['title' => 'Anniversary EP', 'date' => '2024-02-28', 'ep' => true, 'download' => true, 'tracks' => ['藍 Live at Asylum Chapel (South London)', '奏（かなで） Live at Asylum Chapel (South London)', '未来花 for Anniversary', 'ボクノート ～for 20th Anniversary with Orchestra～']],
            // CDシングル
            ['title' => 'view', 'date' => '2003-07-09', 'tracks' => ['view', '小さな手']],
            ['title' => '奏（かなで）', 'date' => '2004-03-10', 'tracks' => ['奏（かなで）', '僕の話 -プロトタイプ-', '蕾のテーマ<Instrumental>', '奏（かなで）<Backing Track>']],
            ['title' => 'ふれて未来を', 'date' => '2004-06-16', 'tracks' => ['ふれて未来を', '雨は止まない', '天白川を行く<Instrumental>', 'ふれて未来を<Backing track>']],
            ['title' => '冬の口笛', 'date' => '2004-11-24', 'tracks' => ['冬の口笛', '弦楽四重奏のための『ドーシタトースター』', '追伸(instrumental)', '冬の口笛(backing track)']],
            ['title' => '全力少年', 'date' => '2005-04-20', 'tracks' => ['全力少年', 'さみしくとも明日を待つ', '花曇りの午後(instrumental)', '全力少年(backing track)']],
            ['title' => '雨待ち風', 'date' => '2005-06-22', 'tracks' => ['雨待ち風', '青春騎士', '安曇野にて(instrumental)', '雨待ち風(backing track)']],
            ['title' => 'ボクノート', 'date' => '2006-03-01', 'tracks' => ['ボクノート', '猫になれ', '若葉<instrumental>', 'ボクノート<backing track>']],
            ['title' => 'ガラナ', 'date' => '2006-08-16', 'tracks' => ['ガラナ', 'スフィアの羽根', 'ピーカンブギ<instrumental>', 'ガラナ<backing track>']],
            ['title' => 'アカツキの詩', 'date' => '2006-11-22', 'tracks' => ['アカツキの詩', '君曜日', '夕間暮れ<instrumental>', 'アカツキの詩<backing track>']],
            ['title' => 'マリンスノウ', 'date' => '2007-07-11', 'tracks' => ['マリンスノウ', '回想目盛', 'ノウムの調べ<instrumental>', 'マリンスノウ<backing track>']],
            ['title' => '虹のレシピ', 'date' => '2009-05-20', 'tracks' => ['虹のレシピ', '雫', 'Aアングル', '虹のレシピ(backing track)']],
            ['title' => 'ゴールデンタイムラバー', 'date' => '2009-10-14', 'tracks' => ['ゴールデンタイムラバー', 'ためいき', 'Bアングル', 'ゴールデンタイムラバー(backing track)']],
            ['title' => 'アイスクリーム シンドローム', 'date' => '2010-07-07', 'tracks' => ['アイスクリーム シンドローム', '夕凪', '1017小節のラブソング', 'アイスクリーム シンドローム(backing track)']],
            ['title' => 'さいごのひ', 'date' => '2011-01-26', 'tracks' => ['さいごのひ', '電話キ', 'Human relations', 'さいごのひ(backing track)']],
            ['title' => '晴ときどき曇', 'date' => '2011-09-14', 'tracks' => ['晴ときどき曇', '石コロDays', 'ガレポンク<Instrumental>', '晴ときどき曇<backing track>']],
            ['title' => 'ラストシーン', 'date' => '2012-06-27', 'tracks' => ['ラストシーン', 'またね。(betsu-oke ver.)', 'フォノグラフ(Instrumental)', 'ラストシーン(backing track)']],
            ['title' => 'ユリーカ', 'date' => '2012-08-08', 'tracks' => ['ユリーカ', 'きみがいいなら(from「TOUR 2012“musium”」)', 'ガラナ(from「TOUR 2012“musium”」)', 'ユリーカ(backing track)']],
            ['title' => 'スカーレット', 'date' => '2013-06-19', 'tracks' => ['スカーレット', 'トラベラーズ・ハイ', '10th(instrumental)', 'スカーレット(backing track)']],
            ['title' => 'Hello Especially', 'date' => '2013-07-31', 'tracks' => ['Hello Especially', 'サウンドオブ', '10th -typeII-(instrumental)', 'Hello Especially(backing track)']],
            ['title' => 'Ah Yeah!!', 'date' => '2014-07-23', 'tracks' => ['Ah Yeah!!', '夏のコスモナウト', 'passage（from 新宿LOFT 2014.4.9）', 'Ah Yeah!!（backing track）']],
            ['title' => 'パラボラヴァ', 'date' => '2014-11-19', 'tracks' => ['パラボラヴァ', '僕の青い自転車', '君の話（from 新宿LOFT 2014.4.9）', 'パラボラヴァ（backing track）']],
            ['title' => '星のうつわ', 'date' => '2014-12-03', 'tracks' => ['星のうつわ', '快楽のソファー（仮）2014 ver.', 'ミッドナイト・グッドモーニン!!のテーマ（instrumental）', 'スキマスイッチのミッドナイト・グッドモーニン!!', '星のうつわ（backing track）']],
            ['title' => 'LINE', 'date' => '2015-11-11', 'tracks' => ['LINE', 'ハナツ', 'スキマスイッチのミッドナイト・グッドモーニン!! -2-', 'LINE（backing track）', 'ハナツ（backing track）']],
            ['title' => '全力少年 produced by 奥田民生', 'date' => '2016-11-30', 'tracks' => ['全力少年 produced by 奥田民生', 'ハナツ premium ver.', '全力少年 produced by 奥田民生（anime ver.）', '全力少年 produced by 奥田民生（KARAOKE）']],
            ['title' => 'ミスターカイト / リチェルカ', 'date' => '2017-09-13', 'tracks' => ['ミスターカイト', 'リチェルカ', 'さよならエスケープ', 'ココロシティ']],
            ['title' => '青春', 'date' => '2019-07-03', 'tracks' => ['青春', '東京', '糸']],
            ["title" => "Lovin' Song", 'date' => '2024-02-21', 'tracks' => ["Lovin' Song", 'Revival（Live Full Course 2022 at Nippon Budokan[22.12.22]）', 'Revival（TOUR 2020-2021 Smoothie at Nagoya Century Hall[21.1.29]）', "Lovin' Song（Instrumental）"]],
            // 通販限定シングル
            ['title' => 'クリスマスがやってくる〜Christmas Edition〜', 'date' => '2019-10-31', 'download' => true, 'tracks' => ['クリスマスがやってくる', 'This Christmas', 'The Christmas Song', 'Crazy Love']],
            ['title' => 'up!!!!!!(DELUXE盤)', 'date' => '2022-08-24', 'download' => true, 'tracks' => ['up!!!!!!', 'up!!!!!! feat. Rouno', 'up!!!!!!(Instrumental)', 'up!!!!!! feat. Rouno(Instrumental)']],
            // 配信限定シングル
            ['title' => 'センチメンタル ホームタウン', 'date' => '2011-07-09', 'download' => true, 'tracks' => ['センチメンタル ホームタウン']],
            ['title' => '石コロDays', 'date' => '2011-08-13', 'download' => true, 'tracks' => ['石コロDays']],
            ['title' => 'さよならエスケープ', 'date' => '2017-01-16', 'download' => true, 'tracks' => ['さよならエスケープ']],
            ['title' => '奏（かなで）for 一週間フレンズ。', 'date' => '2017-02-02', 'download' => true, 'tracks' => ['奏（かなで）for 一週間フレンズ。']],
            ['title' => '未来花(ミライカ) for Anniversary', 'date' => '2018-05-09', 'download' => true, 'tracks' => ['未来花(ミライカ) for Anniversary']],
            ['title' => 'クリスマスがやってくる', 'date' => '2018-12-05', 'download' => true, 'tracks' => ['クリスマスがやってくる']],
            ['title' => '青春', 'date' => '2019-03-28', 'download' => true, 'tracks' => ['青春']],
            ['title' => '全力少年 Remastered', 'date' => '2020-04-03', 'download' => true, 'tracks' => ['全力少年 Remastered']],
            ['title' => '吠えろ!', 'date' => '2021-04-14', 'download' => true, 'tracks' => ['吠えろ!']],
            ['title' => '茜', 'date' => '2021-06-29', 'download' => true, 'tracks' => ['茜']],
            ['title' => 'OverDriver（すりぃRemix）', 'date' => '2022-06-15', 'download' => true, 'tracks' => ['OverDriver（すりぃRemix）']],
            ['title' => 'up!!!!!!', 'date' => '2022-07-09', 'download' => true, 'tracks' => ['up!!!!!!']],
            ['title' => 'コトバリズム', 'date' => '2023-09-06', 'download' => true, 'tracks' => ['コトバリズム']],
            ["title" => "Lovin' Song", 'date' => '2024-01-06', 'download' => true, 'tracks' => ["Lovin' Song"]],
            ['title' => 'あの日の虹と僕らのアンセム', 'date' => '2025-05-07', 'download' => true, 'tracks' => ['あの日の虹と僕らのアンセム']],
            ['title' => 'トリオス', 'date' => '2026-05-27', 'download' => true, 'tracks' => ['トリオス']],
        ];
    }
}
