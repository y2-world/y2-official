<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

class ImportHigedanDiscography extends Command
{
    protected $signature = 'import:higedan-discography {--dry-run : Show matching without saving}';

    protected $description = 'Import Official髭男dism albums and singles, matching tracklist songs to existing DbSong records';

    private const ARTIST_ID = 27;

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

    // DbSongに存在しない短いインタールード/SE的トラック（正式な楽曲としてDbSong化されていない）。
    // カラオケと同様、実演奏曲ではないためidを持たせずexceptionのみで表示する。
    private const NON_SONG_TRACKS = [
        '052519',
        "I'm home（Interlude）",
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

            $isCd = empty($singleData['download']);
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
            // ミニアルバム（インディーズ時代）
            [
                'title' => 'ラブとピースは君の中',
                'date' => '2015-04-22',
                'mini' => true,
                'tracks' => ['SWEET TWEET', '恋の前ならえ', '夕暮れ沿い', '雪急ぐ朝が来る', '始発が導く幸福論', '愛なんだが…', 'parade', 'ダーリン。'],
            ],
            [
                'title' => 'MAN IN THE MIRROR',
                'date' => '2016-06-15',
                'mini' => true,
                'tracks' => ['Clap Clap', 'コーヒーとシロップ', 'Happy Birthday To You', '恋の去り際', 'ゼロのままでいられたら', '日曜日のラブレター'],
            ],
            [
                'title' => 'レポート',
                'date' => '2017-04-19',
                'mini' => true,
                'tracks' => ['始まりの朝', '犬かキャットかで死ぬまで喧嘩しよう！', '異端なスター', '55', 'Rolling', 'イコール', 'Trailer'],
            ],
            // CD EP
            [
                'title' => "What's Going On?",
                'date' => '2016-11-02',
                'mini' => true,
                'tracks' => ["What's Going On?", '未完成なままで', 'ニットの帽子', '黄色い車'],
            ],
            [
                'title' => 'Stand By You EP',
                'date' => '2018-10-17',
                'mini' => true,
                'tracks' => ['Stand By You', 'FIRE GROUND', 'バッドフォーミー', 'Stand By You (Acoustic ver.)'],
            ],
            [
                'title' => 'HELLO EP',
                'date' => '2020-08-05',
                'mini' => true,
                'tracks' => ['HELLO', 'パラボラ', 'Laughter', '夏模様の猫'],
            ],
            [
                'title' => 'ミックスナッツ EP',
                'date' => '2022-06-22',
                'mini' => true,
                'tracks' => ['ミックスナッツ', 'Anarchy', 'Choral A', '破顔'],
            ],
            // 配信限定EP
            [
                'title' => 'LADY',
                'date' => '2017-10-13',
                'mini' => true,
                'tracks' => ['LADY', 'Driver', 'Tell Me Baby', 'ブラザーズ'],
            ],
            // フルアルバム
            [
                'title' => 'エスカパレード',
                'date' => '2018-04-11',
                'tracks' => ['115万キロのフィルム', 'ノーダウト', 'ESCAPADE', 'LADY', 'たかがアイラブユー', 'されど日々は', '可能性', 'Tell Me Baby', 'Second LINE', 'Driver', '相思相愛', 'ブラザーズ', '発明家'],
            ],
            [
                'title' => 'Traveler',
                'date' => '2019-10-09',
                'tracks' => ['イエスタデイ', '宿命', 'Amazing', 'Rowan', 'バッドフォーミー', '最後の恋煩い', 'ビンテージ', 'Stand By You', 'FIRE GROUND', '旅は道連れ', '052519', 'Pretender', 'ラストソング', 'Travelers'],
            ],
            [
                'title' => 'Editorial',
                'date' => '2021-08-18',
                'tracks' => ['Editorial', 'アポトーシス', 'I LOVE...', 'フィラメント', 'HELLO', 'Cry Baby', 'Shower', 'みどりの雨避け', 'パラボラ', 'ペンディング・マシーン', 'Bedroom Talk', 'Laughter', 'Universe', 'Lost In My Room'],
            ],
            [
                'title' => 'Rejoice',
                'date' => '2024-07-31',
                'tracks' => ['Finder', 'Get Back To 人生', 'ミックスナッツ', 'SOULSOUP', 'キャッチボール', '日常', "I'm home（Interlude）", 'Sharon', '濁点', 'Subtitle', 'Anarchy（Rejoice ver.）', 'ホワイトノイズ', 'うらみつらみきわみ', 'Chessboard', 'TATTOO', 'B-Side Blues'],
            ],
        ];
    }

    private function singlesData(): array
    {
        return [
            // CDシングル
            ['title' => 'ノーダウト', 'date' => '2018-04-11', 'tracks' => ['ノーダウト']],
            ['title' => 'Pretender', 'date' => '2019-05-15', 'tracks' => ['Pretender', 'Amazing', 'Pretender (Acoustic Ver)']],
            ['title' => '宿命', 'date' => '2019-07-31', 'tracks' => ['宿命', '宿命 (Instrumental)']],
            ['title' => 'I LOVE...', 'date' => '2020-02-12', 'tracks' => ['I LOVE...', 'I LOVE...（Instrumental）']],
            ['title' => 'Universe', 'date' => '2021-02-24', 'tracks' => ['Universe', 'Universe -Instrumental-']],
            ['title' => 'Chessboard/日常', 'date' => '2023-09-13', 'tracks' => ['Chessboard', '日常', 'Chessboard -Instrumental-', '日常 -Instrumental-']],
            ['title' => 'スターダスト/エルダーフラワー', 'date' => '2026-04-22', 'tracks' => ['スターダスト', 'エルダーフラワー', 'スターダスト -Instrumental-', 'エルダーフラワー -Instrumental-']],
            // 配信限定シングル
            ['title' => 'Tell Me Baby/ブラザーズ', 'date' => '2017-07-21', 'download' => true, 'tracks' => ['Tell Me Baby', 'ブラザーズ']],
            ['title' => 'バッドフォーミー', 'date' => '2018-08-06', 'download' => true, 'tracks' => ['バッドフォーミー']],
            ['title' => 'パラボラ', 'date' => '2020-04-10', 'download' => true, 'tracks' => ['パラボラ']],
            ['title' => 'Laughter', 'date' => '2020-07-10', 'download' => true, 'tracks' => ['Laughter']],
            ['title' => 'Cry Baby', 'date' => '2021-05-07', 'download' => true, 'tracks' => ['Cry Baby']],
            ['title' => 'Anarchy', 'date' => '2022-01-07', 'download' => true, 'tracks' => ['Anarchy']],
            ['title' => 'ミックスナッツ', 'date' => '2022-04-15', 'download' => true, 'tracks' => ['ミックスナッツ']],
            ['title' => 'Subtitle', 'date' => '2022-10-12', 'download' => true, 'tracks' => ['Subtitle']],
            ['title' => 'ホワイトノイズ', 'date' => '2023-01-11', 'download' => true, 'tracks' => ['ホワイトノイズ']],
            ['title' => 'TATTOO', 'date' => '2023-04-21', 'download' => true, 'tracks' => ['TATTOO']],
            ['title' => 'Chessboard', 'date' => '2023-08-09', 'download' => true, 'tracks' => ['Chessboard']],
            ['title' => '日常', 'date' => '2023-09-13', 'download' => true, 'tracks' => ['日常']],
            ['title' => 'SOULSOUP', 'date' => '2023-12-13', 'download' => true, 'tracks' => ['SOULSOUP']],
            ['title' => 'Same Blue', 'date' => '2024-10-02', 'download' => true, 'tracks' => ['Same Blue']],
            ['title' => '50%', 'date' => '2024-12-13', 'download' => true, 'tracks' => ['50%']],
            ['title' => 'らしさ', 'date' => '2025-08-06', 'download' => true, 'tracks' => ['らしさ']],
            ['title' => 'Sanitizer', 'date' => '2025-12-01', 'download' => true, 'tracks' => ['Sanitizer']],
            ['title' => 'Make Me Wonder', 'date' => '2025-12-29', 'download' => true, 'tracks' => ['Make Me Wonder']],
            ['title' => 'エルダーフラワー', 'date' => '2026-03-30', 'download' => true, 'tracks' => ['エルダーフラワー']],
            ['title' => 'スターダスト', 'date' => '2026-04-13', 'download' => true, 'tracks' => ['スターダスト']],
        ];
    }
}
