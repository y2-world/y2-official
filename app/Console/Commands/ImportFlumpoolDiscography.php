<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

class ImportFlumpoolDiscography extends Command
{
    protected $signature = 'import:flumpool-discography {--dry-run : Show matching without saving}';

    protected $description = 'Import flumpool albums and singles, matching tracklist songs to existing DbSong records';

    private const ARTIST_ID = 4;

    // DbSongに存在しないインスト曲（歌の無い作品曲。他の曲のInstrumental版とは違い、
    // それ自体が最初からインストとして作られた独立トラック）。実演奏曲ではないため
    // idを持たせずexceptionのみで表示する。
    private const NON_SONG_TRACKS = [
        '20080701',
    ];

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

        // titleの表記ゆれ修正のたびにupdateOrCreateのキーがずれて重複レコードが増えてきたため、
        // 同じ日付（同日複数リリースがある場合はtitleも含めて同一キー）に複数レコードがある場合は
        // ID最大（＝最後に作られた最新のもの）以外を削除する
        $singlesByKey = DbSingle::where('artist_id', self::ARTIST_ID)->orderBy('id')->get(['id', 'date', 'title'])->groupBy('date');
        foreach ($singlesByKey as $date => $group) {
            if ($group->count() <= 1) {
                continue;
            }
            $keep = $group->last();
            foreach ($group as $dupe) {
                if ($dupe->id === $keep->id) {
                    continue;
                }
                $this->warn("Removing duplicate single: id={$dupe->id} date={$date} title={$dupe->title}");
                if (!$dryRun) {
                    $dupe->delete();
                }
            }
        }

        $albumsByKey = DbAlbum::where('artist_id', self::ARTIST_ID)->orderBy('id')->get(['id', 'date', 'title'])->groupBy(fn ($a) => $a->date . '|' . $a->title);
        foreach ($albumsByKey as $key => $group) {
            if ($group->count() <= 1) {
                continue;
            }
            $keep = $group->last();
            foreach ($group as $dupe) {
                if ($dupe->id === $keep->id) {
                    continue;
                }
                $this->warn("Removing duplicate album: id={$dupe->id} key={$key}");
                if (!$dryRun) {
                    $dupe->delete();
                }
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

            // specialは他アーティストとのオムニバス盤等、ベスト/ミニ/オリジナルの
            // いずれにも当たらない企画盤。album_idを振らずラベルなしで表示する
            $isOriginal = empty($albumData['best']) && empty($albumData['mini']) && empty($albumData['special']);
            if ($isOriginal) {
                $albumId++;
            }

            $this->line("=== {$albumData['title']} ({$albumData['date']}) === matched " . count($trackIds) . '/' . count($albumData['tracks']));

            if (!$dryRun) {
                $key = ['artist_id' => self::ARTIST_ID, 'date' => $albumData['date']];
                if ($isOriginal) {
                    $key['album_id'] = $albumId;
                } else {
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

            // EPや、DVD/Blu-ray等の円盤に同梱されたボーナスCD（bonus）は、CD/配信いずれの
            // 形態でもCDシングルの通し番号(single_id)には含めない
            $isEp = !empty($singleData['ep']);
            $isBonus = !empty($singleData['bonus']);
            $isCd = empty($singleData['download']) && !$isEp && !$isBonus;
            if ($isCd) {
                $singleId++;
            }

            $this->line("=== [single] {$singleData['title']} ({$singleData['date']}) === matched " . count($trackIds) . '/' . count($singleData['tracks']));

            if (!$dryRun) {
                $key = ['artist_id' => self::ARTIST_ID, 'date' => $singleData['date']];
                if ($isCd) {
                    $key['single_id'] = $singleId;
                } else {
                    $key['title'] = $singleData['title'];
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
                'title' => 'Unreal',
                'date' => '2008-11-19',
                'mini' => true,
                'tracks' => ['花になれ', '春風', 'Over the rain 〜ひかりの橋〜', '388859', 'Hello', 'labo', 'LOST', '未来', ['花になれ', 'Instrumental'], ['Over the rain 〜ひかりの橋〜', 'Instrumental']],
            ],
            [
                'title' => "What's flumpool!?",
                'date' => '2009-12-23',
                'tracks' => ['Calling', '星に願いを', '見つめていたい', 'MW 〜Dear Mr. & Mrs. ピカレスク〜', '僕は偶然を待っているらしい', '回転木馬 (メリーゴーランド)', '車窓', 'Hills', '夏Dive', 'LOVE 2010', 'Quville', '最後のページ', '今年の桜', 'タイムカプセル', 'サイレン', 'フレイム'],
            ],
            [
                'title' => 'Fantasia of Life Stripe',
                'date' => '2011-01-26',
                'tracks' => ['君に届け', 'two of us', 'reboot 〜あきらめない詩〜', 'Snowy Nights Serenade 〜心までも繋ぎたい〜', '東京哀歌', 'Music Surfer', '僕はここにいる', '君のための100のもしも', 'この時代を生き抜くために', '流れ星', 'ギルト', 'しおり', 'ベガ 〜過去と未来の北極星〜', '残像'],
            ],
            [
                'title' => 'experience',
                'date' => '2012-12-12',
                'tracks' => ['どんな未来にも愛はある', 'Answer', 'イイじゃない?', 'Across the Times', 'プレミアム・ガール', 'Sprechchor', 'Because... I am', '証', 'Natural Venus', '傘の下で君は･･･', '覚醒アイデンティティ', 'The great escape', 'Touch', '36℃'],
            ],
            [
                'title' => 'EGG',
                'date' => '2016-03-16',
                'tracks' => ['解放区', '夜は眠れるかい?', 'World beats', '今日の誓い', 'DILEMMA', '絶体絶命!!!', 'LINE', 'Dear my friend', '産声', 'とある始まりの情景 〜Bookstore on the Hill〜', '夏よ止めないで 〜You’re Romantic〜', '輪廻', 'Blue Apple & Red Banana', '明日キミが泣かないように'],
            ],
            [
                'title' => 'Real',
                'date' => '2020-05-20',
                'tracks' => ['20080701', 'NEW DAY DREAMER', 'ネバーマインド', 'ディスカス', '不透明人間', 'ちいさな日々', '初めて愛をくれた人', '勲章', '素晴らしき嘘', 'ほうれん草のソテー', 'アップデイト', 'PEPEパラダイス', '虹の傘', 'HELP'],
            ],
            [
                'title' => 'A Spring Breath',
                'date' => '2022-03-16',
                'tracks' => ['君に届け (A Spring Breath ver.)', 'サヨナラの瞬間', 'two of us (A Spring Breath ver.)', '明日への帰り道', '証 (A Spring Breath ver.)', '夢から醒めないで', 'Hydrangea (A Spring Breath ver.)', '誰かの春の風になって', 'どんな未来にも愛はある (A Spring Breath ver.)', '花になれ (A Spring Breath ver.)', 'A Spring Breath'],
            ],
            [
                'title' => 'Shape the water',
                'date' => '2025-03-05',
                'tracks' => ['Keep it up!!', 'アラシノヨルニ', 'Love’s Ebb and Flow', 'Just stand by me', 'ハレルヤ・レディ', 'Hourglass', 'SUMMER LION', '夕日に染められて君は', '星空サイクリング', '迷宮シナプス', 'puzzled', 'Sugarsong', '君に恋したあの日から'],
            ],
            [
                'title' => 'ここからの歌',
                'date' => '2026-06-24',
                'mini' => true,
                'tracks' => ['STAR EXTRA', 'Ring', 'Twist & Shout', ['スノウゴースト', 'Special ver.']],
            ],
            // ベストアルバム
            [
                'title' => 'The Best 2008-2014「MONUMENT」',
                'date' => '2014-05-21',
                'best' => true,
                'tracks' => [
                    ['花になれ', 'Disc-1'], ['星に願いを', 'Disc-1'], ['どんな未来にも愛はある', 'Disc-1'], ['春風', 'Disc-1'], ['君をつれて', 'Disc-1'], ['微熱リフレイン', 'Disc-1'], ['Belief 〜春を待つ君へ〜', 'Disc-1'], ['MW 〜Dear Mr. & Mrs. ピカレスク〜', 'Disc-1'], ['labo (Re-format)', 'Disc-1'], ['two of us', 'Disc-1'], ['強く儚く', 'Disc-1'], ['Over the rain 〜ひかりの橋〜', 'Disc-1'], ['ビリーバーズ・ハイ', 'Disc-1'], ['覚醒アイデンティティ', 'Disc-1'], ['証', 'Disc-1'],
                    ['明日への賛歌', 'Disc-2'], ['reboot 〜あきらめない詩〜', 'Disc-2'], ['大切なものは君以外に見当たらなくて', 'Disc-2'], ['今年の桜', 'Disc-2'], ['残像', 'Disc-2'], ['Because... I am', 'Disc-2'], ['Touch', 'Disc-2'], ['Answer', 'Disc-2'], ['イイじゃない?', 'Disc-2'], ['見つめていたい', 'Disc-2'], ['Hydrangea', 'Disc-2'], ['Snowy Nights Serenade 〜心までも繋ぎたい〜 (Choral Xmas ver.)', 'Disc-2'], ['夏Dive', 'Disc-2'], ['君に届け', 'Disc-2'], ['フレイム', 'Disc-2'],
                ],
            ],
            [
                'title' => 'The Best flumpool 2.0 〜 Blue［2008-2011］& Red［2019-2023］〜',
                'date' => '2023-10-09',
                'best' => true,
                'tracks' => [
                    ['花になれ (Ryu-Take 2023 ver.)', 'Disc-1'], ['Over the rain 〜ひかりの橋〜 (Ryu-Take 2023 ver.)', 'Disc-1'], ['春風 (Ryu-Take 2023 ver.)', 'Disc-1'], ['星に願いを (Ryu-Take 2023 ver.)', 'Disc-1'], ['MW 〜Dear Mr. & Mrs. ピカレスク〜 (Ryu-Take 2023 ver.)', 'Disc-1'], ['フレイム (Ryu-Take 2023 ver.)', 'Disc-1'], ['君に届け (Ryu-Take 2023 ver.)', 'Disc-1'], ['僕はここにいる (Ryu-Take 2023 ver.)', 'Disc-1'], ['two of us (Ryu-Take 2023 ver.)', 'Disc-1'], ['証 (Ryu-Take 2023 ver.)', 'Disc-1'],
                    ['その次に', 'Disc-2'], ['泣いていいんだ', 'Disc-2'], ['Magic', 'Disc-2'], ['素晴らしき嘘', 'Disc-2'], ['ビギナーズノート', 'Disc-2'], ['ディスタンス', 'Disc-2'], ['ちいさな日々', 'Disc-2'], ['青空ブランニュー', 'Disc-2'], ['ヒアソビ', 'Disc-2'], ['A Spring Breath', 'Disc-2'], ['HELP', 'Disc-2'],
                ],
            ],
        ];
    }

    private function singlesData(): array
    {
        return [
            ['title' => '星に願いを', 'date' => '2009-02-25', 'tracks' => ['星に願いを', '回転木馬 (メリーゴーランド)', '春風 「No Reply」Unplugged', '星に願いを (Instrumental)', '回転木馬 (メリーゴーランド) (Instrumental)', '春風 「No Reply」Unplugged (Instrumental)']],
            ['title' => 'MW 〜Dear Mr. & Mrs. ピカレスク〜 / 夏Dive', 'date' => '2009-07-01', 'tracks' => ['MW 〜Dear Mr. & Mrs. ピカレスク〜', '夏Dive', 'サイレン', 'MW 〜Dear Mr. & Mrs. ピカレスク〜 (Instrumental)', '夏Dive (Instrumental)']],
            ['title' => '残像', 'date' => '2010-02-03', 'tracks' => ['残像', 'Birds', '今年の桜 (Graduation Remix)']],
            ['title' => 'reboot 〜あきらめない詩〜 / 流れ星', 'date' => '2010-06-23', 'tracks' => ['reboot 〜あきらめない詩〜', '流れ星', 'Calling (LIVE at C.C.Lemon Hall)', 'reboot 〜あきらめない詩〜 (Instrumental)', '流れ星 (Instrumental)']],
            ['title' => '君に届け', 'date' => '2010-09-29', 'tracks' => ['君に届け', '僕の存在', 'Quville (LIVE at C.C.Lemon Hall)', '君に届け (Instrumental)', '僕の存在 (Instrumental)']],
            ['title' => 'どんな未来にも愛はある / Touch', 'date' => '2011-07-27', 'tracks' => ['どんな未来にも愛はある', 'Touch', 'two of us (Live at Kanagawa Kenmin Hall)', '君のための100のもしも (Live)', '君に届け (Live)', 'どんな未来にも愛はある (Instrumental)', 'Touch (Instrumental)']],
            ['title' => '証', 'date' => '2011-09-07', 'tracks' => ['証', '覚醒アイデンティティ', 'for no one', '証 (flumpool×Choir〜合唱ver.)']],
            ['title' => 'Present', 'date' => '2011-12-07', 'tracks' => ['Present', '『ありがとう』くらいじゃ伝えられない気持ちを', 'Happy Xmas (War Is Over)', 'Present (Instrumental)', '『ありがとう』くらいじゃ伝えられない気持ちを (Instrumental)']],
            ['title' => 'Because... I am', 'date' => '2012-07-11', 'tracks' => ['Because... I am', 'サマータイムブルース', 'Because... I am (Summer DIVE 2012 〜into the Blue〜)', 'Because... I am (Instrumental)']],
            ['title' => 'Answer', 'date' => '2012-11-07', 'tracks' => ['Answer', '君をつれて', 'Because... I am (live, 5th tour 2012)', 'Answer (Instrumental)']],
            ['title' => '大切なものは君以外に見当たらなくて / 微熱リフレイン', 'date' => '2013-07-03', 'tracks' => ['大切なものは君以外に見当たらなくて', '微熱リフレイン', 'OAOA', '大切なものは君以外に見当たらなくて (Instrumental)', '微熱リフレイン (Instrumental)', 'OAOA (Instrumental)']],
            ['title' => '強く儚く / Belief 〜春を待つ君へ〜', 'date' => '2013-10-02', 'tracks' => ['強く儚く', 'Belief 〜春を待つ君へ〜 (flumpool×Mayday)', 'brilliant days']],
            ['title' => 'FOUR ROOMS', 'date' => '2015-05-13', 'tracks' => ['とある始まりの情景 〜Bookstore on the Hill〜', '歓喜のフィドル', 'MY HOME TOWN', '大好きだった']],
            ['title' => '夏よ止めないで 〜You’re Romantic〜', 'date' => '2015-08-05', 'tracks' => ['夏よ止めないで 〜You’re Romantic〜', 'キミがいたから', 'Let Tomorrow Be', '夏よ止めないで 〜You’re Romantic〜 (Instrumental)', 'キミがいたから (Instrumental)', 'Let Tomorrow Be (Instrumental)']],
            ['title' => '夜は眠れるかい?', 'date' => '2016-02-10', 'tracks' => ['夜は眠れるかい?', '君が笑えば 〜Just like happiness〜', 'MOMENT', '夜は眠れるかい? (Instrumental)', '君が笑えば 〜Just like happiness〜 (Instrumental)']],
            ['title' => 'FREE YOUR MIND', 'date' => '2016-11-02', 'tracks' => ['FREE YOUR MIND', 'ムーンライト・トリップ', 'labo (live, FM802 MEET THE WORLD BEAT 2016)', 'Blue Apple & Red Banana (live, SWEET LOVE SHOWER 2016)']],
            ['title' => 'ラストコール', 'date' => '2017-03-15', 'tracks' => ['ラストコール', 'ナミダリセット', 'キズナキズ', 'ラストコール (Instrumental)', 'ナミダリセット (Instrumental)', 'キズナキズ (Instrumental)']],
            ['title' => 'とうとい', 'date' => '2017-12-26', 'tracks' => ['とうとい', 'To be continued…', 'WINNER', 'とうとい (Instrumental)', 'To be continued… (Instrumental)', 'WINNER (Instrumental)']],
            ['title' => 'HELP', 'date' => '2019-05-22', 'tracks' => ['HELP', '空の旅路', 'HOPE', 'つながり']],
            ['title' => '素晴らしき嘘', 'date' => '2020-02-26', 'tracks' => ['素晴らしき嘘', 'ネバーマインド', '388859 (live)', 'HELP (live)', '春風 (live)']],
            ['title' => '大丈夫', 'date' => '2020-12-25', 'download' => true, 'tracks' => ['大丈夫']],
            ['title' => 'ディスタンス', 'date' => '2021-05-26', 'tracks' => ['ディスタンス', 'フリーズ', '大丈夫']],
            ['title' => 'その次に', 'date' => '2021-10-01', 'download' => true, 'tracks' => ['その次に']],
            ['title' => 'Magic', 'date' => '2023-04-07', 'download' => true, 'tracks' => ['Magic']],
            ['title' => 'ヒアソビ', 'date' => '2023-07-07', 'download' => true, 'tracks' => ['ヒアソビ']],
            ['title' => 'Over the rain 〜ひかりの橋〜 (Ryu-Take 2023 ver.)', 'date' => '2023-08-15', 'download' => true, 'tracks' => ['Over the rain 〜ひかりの橋〜 (Ryu-Take 2023 ver.)']],
            ['title' => '花になれ (Ryu-Take 2023 ver.)', 'date' => '2023-09-15', 'download' => true, 'tracks' => ['花になれ (Ryu-Take 2023 ver.)']],
            ['title' => '泣いていいんだ', 'date' => '2023-10-05', 'download' => true, 'tracks' => ['泣いていいんだ']],
            ['title' => '君に恋したあの日から', 'date' => '2024-04-03', 'download' => true, 'tracks' => ['君に恋したあの日から']],
            ['title' => 'いきづく feat. Nao Matsushita', 'date' => '2024-06-05', 'download' => true, 'tracks' => ['いきづく']],
            ['title' => 'SUMMER LION', 'date' => '2024-07-03', 'download' => true, 'tracks' => ['SUMMER LION']],
            ['title' => 'スノウゴースト', 'date' => '2025-12-03', 'download' => true, 'tracks' => ['スノウゴースト']],
        ];
    }
}
