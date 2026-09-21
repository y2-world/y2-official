<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

class ImportWindsDiscography extends Command
{
    protected $signature = 'import:winds-discography {--dry-run : Show matching without saving}';

    protected $description = 'Import w-inds. albums and singles, matching tracklist songs to existing DbSong records';

    private const ARTIST_ID = 1;

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

    // DbSongに存在しないリミックス違いのトラック（別バージョンとして正式にDbSong化はしない）。
    // カラオケと同様、実演奏曲ではないためidを持たせずexceptionのみで表示する。
    private const NON_SONG_TRACKS = [
        'SUPER LOVER〜movin’ pleasure mix〜',
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
                // tracksの各要素は曲名の文字列、または ['曲名', ディスク番号] の配列（複数ディスク構成のアルバム用）
                $trackTitle = is_array($trackEntry) ? $trackEntry[0] : $trackEntry;
                $disc = is_array($trackEntry) ? $trackEntry[1] : null;

                $match = $this->findSong($songsByNormalizedTitle, $songTitlesById, $trackTitle);
                if ($match === null) {
                    $unmatched[] = $albumData['title'] . ' / ' . $trackTitle;
                    continue;
                }
                $isKaraoke = isKaraokeTrack($match['exception']);
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
                'title' => 'w-inds.〜1st message〜',
                'date' => '2001-12-19',
                'tracks' => ['You can’t get away', 'Winding Road', 'Feel The Fate', 'Winter Story', 'Give you my heart', 'Paradox (rumor style)', 'The New Generation', 'Forever Memories', 'Love you anymore', 'ROUND & ROUND', 'Endless Moment', 'New-age Dreams'],
            ],
            [
                'title' => 'w-inds.〜THE SYSTEM OF ALIVE〜',
                'date' => '2002-12-18',
                'tracks' => ['Break Down, Build Up', 'NEW PARADISE', 'SOMEHOW', 'fever', 'Baby Maybe', 'THE SYSTEM OF ALIVE', 'Because of you', 'try your emotion 〜next side version〜', 'THANK YOU', 'I still love you', 'Find Myself', 'Another Days', 'This Time 〜願い〜', 'Top of the world'],
            ],
            [
                'title' => 'w-inds.〜PRIME OF LIFE〜',
                'date' => '2003-12-17',
                'tracks' => ['Love Train', 'SUPER LOVER 〜I need you tonight〜', 'whose is that girl?', 'W.O.L. (Wonder Of Love)', 'Long Road', '空から降りてきた白い星', 'GAME', 'so what?', 'Love is message', 'Ex-Girlfriend', 'Dedicated to You', 'INFINITY', 'Deny', 'SUPER LOVER〜movin’ pleasure mix〜'],
            ],
            [
                'title' => 'ageha',
                'date' => '2005-06-01',
                'tracks' => ['Lil’ Crazy', 'Party Down', 'キレイだ', '夏空の恋の詩', '四季', 'ageha', 'song 4 U', 'Pieces', '変わりゆく空', '夢の場所へ', 'マバタキの夢', 'Color me', 'タイムマシーン', 'Gift'],
            ],
            [
                'title' => 'THANKS',
                'date' => '2006-03-15',
                'tracks' => ['1 or 8', 'Hush…!', 'Still on the Street', 'Midnight Venus', 'IT’S IN THE STARS', 'LIGHT', '十六夜の月', '約束のカケラ', '影法師', 'Balance', 'Stomp', 'Sup Wassup!!', '蝉時雨'],
            ],
            [
                'title' => 'Journey',
                'date' => '2007-03-07',
                'tracks' => ['THIS IS OUR SHOW', 'Top Secret', 'Is that you', 'Crazy for You', 'Devil', 'TRIAL', '遠い記憶', 'Milky Way', 'Journey', 'メッセージ', '地図なき旅路', 'Celebration (2007)', 'ブギウギ66', 'TRIANGLE', 'ハナムケ'],
            ],
            [
                'title' => 'Seventh Ave.',
                'date' => '2008-07-02',
                'tracks' => ['Spinning Around', 'RELOADED', 'Hello', 'TOKYO', 'New Day', 'LOVE', 'Urban Dance', 'Rock it', 'アメあと', 'LOVE IS THE GREATEST THING', 'Stay', 'Don’t Give Up', 'Hand in Hand', 'Beautiful Life', 'Summer Days'],
            ],
            [
                'title' => 'Another World',
                'date' => '2010-03-10',
                'tracks' => ['Message', 'New World', 'CAN’T GET BACK', 'Re:vision', 'Nothing Is Impossible', 'Rain Is Fallin’', 'Some More', 'Don’t remind me', 'In The Red', 'Truth 〜最後の真実〜', 'HYBRID DREAM', 'Cos Of You', 'Prayer', 'Spiral', 'Everyday'],
            ],
            [
                'title' => 'MOVE LIKE THIS',
                'date' => '2012-07-04',
                'tracks' => ['T2P', 'Let’s get it on', 'FLY HIGH', 'Listen to the Rain', 'You & I', 'MAKE IT ROCK', 'Addicted to love', 'Superstar', '黄昏One Way', 'Be As One', 'SAY YES', 'Touch The Sky'],
            ],
            [
                'title' => 'Timeless',
                'date' => '2014-07-09',
                'tracks' => ['Do Your Actions', 'Good times', 'Make you mine', 'Feeling U', 'killin’ me', '夢で逢えるのに 〜Sometimes I Cry〜', 'A Little Bit', 'K.O.', 'STEREO', 'Sexy Girl', 'Dream You Back'],
            ],
            [
                'title' => 'Blue Blood',
                'date' => '2015-07-08',
                'tracks' => ['Beyond The Blue World', 'In Love With The Music', 'Show You Tonight', 'LOUD', 'I’m all yours', 'CANDLE NIGHT', 'Cat Walk', 'This is the Life', 'The Right Thing', '1 3 4', 'TIME TO GETDOWN', 'FANTASY'],
            ],
            [
                'title' => 'INVISIBLE',
                'date' => '2017-03-15',
                'tracks' => ['Boom Word Up', 'Come Back to Bed', 'Complicated', 'We Don’t Need To Talk Anymore', 'CAMOUFLAGE', 'Backstage', 'Separate Way', 'ORIGINAL LOVE', 'In your warmth', 'wind wind blow', 'TABOO', 'Players'],
            ],
            [
                'title' => '100',
                'date' => '2018-07-04',
                'tracks' => ['Bring back the summer', 'Dirty Talk', 'Temporary', 'I missed you', 'Celebration', 'We Gotta Go', 'Time Has Gone', 'Stay Gold', 'The love', 'All my love is here for you', 'Drive All Night', 'Sugar'],
            ],
            [
                'title' => '20XX "We are"',
                'date' => '2021-11-24',
                'tracks' => ['Strip', 'EXIT', 'With You', 'Show Me Your Love', 'Little', 'Beautiful Now', 'The Christmas Song (feat. DA PUMP & Lead)', 'Distance', 'Get Down', 'DoU'],
            ],
            [
                'title' => 'Beyond',
                'date' => '2023-03-14',
                'tracks' => ['Unforgettable', 'FIND ME', 'Bang! Bang! (feat. CrazyBoy)', 'Fighting For You', 'Over The Years', 'Blessings', 'I Swear', 'Delete Enter', 'Lost & Found'],
            ],
            [
                'title' => 'winderlust',
                'date' => '2025-03-26',
                'tracks' => ['Zip It', 'Run', 'One more time', 'Rookies', 'FAKE IT', 'Who’s the Liar', 'Look at me', 'Feel the beat', 'Like a fam (feat. 島袋寛子, 谷内伸也 & KIMI)', 'Imagination'],
            ],
            // ベストアルバム
            [
                'title' => 'w-inds.〜bestracks〜',
                'date' => '2004-07-14',
                'best' => true,
                'tracks' => [
                    'Forever Memories', 'Feel The Fate', 'Paradox', 'New-age Dreams', 'try your emotion', 'Another Days', 'SOMEHOW', 'Because of you', 'NEW PARADISE', 'This Time 〜願い〜',
                    'SUPER LOVER 〜I need you tonight〜', 'Love is message', 'Long Road', 'Dedicated to You', 'Pieces', 'キレイだ',
                ],
            ],
            [
                'title' => 'w-inds.Single Collection "BEST ELEVEN"',
                'date' => '2008-01-01',
                'best' => true,
                'tracks' => [
                    ['四季', 'MVP'], ['夢の場所へ', 'MVP'], ['変わりゆく空', 'MVP'], ['十六夜の月', 'MVP'], ['約束のカケラ', 'MVP'], ['IT’S IN THE STARS', 'MVP'], ['TRIAL', 'MVP'], ['ブギウギ66', 'MVP'], ['ハナムケ', 'MVP'], ['LOVE IS THE GREATEST THING', 'MVP'], ['Beautiful Life', 'MVP'],
                    ['INNOVATOR', 'SUPER SUB'], ['Past Tense', 'SUPER SUB'], ['Shangri-La', 'SUPER SUB'], ['Forever Memories 〜2007 Live version〜', 'SUPER SUB'],
                ],
            ],
            [
                'title' => 'w-inds. 10th Anniversary Best Album -We dance for everyone-',
                'date' => '2011-06-22',
                'best' => true,
                'tracks' => [
                    ['Paradox', 'Disc-1'], ['Love you anymore', 'Disc-1'], ['try your emotion', 'Disc-1'], ['Because of you', 'Disc-1'], ['NEW PARADISE', 'Disc-1'], ['Break Down, Build Up', 'Disc-1'], ['SUPER LOVER 〜I need you tonight〜', 'Disc-1'], ['W.O.L. (Wonder Of Love)', 'Disc-1'], ['キレイだ', 'Disc-1'], ['song 4 U', 'Disc-1'], ['IT’S IN THE STARS', 'Disc-1'], ['Back At One', 'Disc-1'], ['ブギウギ66', 'Disc-1'], ['Want ya', 'Disc-1'],
                    ['LOVE IS THE GREATEST THING', 'Disc-2'], ['Beautiful Life', 'Disc-2'], ['I’m a Man', 'Disc-2'], ['CAN’T GET BACK', 'Disc-2'], ['Rain Is Fallin’', 'Disc-2'], ['HYBRID DREAM', 'Disc-2'], ['New World', 'Disc-2'], ['Truth 〜最後の真実〜', 'Disc-2'], ['Addicted to love', 'Disc-2'], ['Let’s get it on', 'Disc-2'], ['Nothing Is Impossible', 'Disc-2'], ['Some More', 'Disc-2'], ['NOTHING IS GONNA CHANGE IT', 'Disc-2'], ['NO DOUBTS', 'Disc-2'],
                ],
            ],
            [
                'title' => 'w-inds. 10th Anniversary Best Album -We sing for you-',
                'date' => '2011-06-22',
                'best' => true,
                'tracks' => [
                    ['Forever Memories', 'Disc-1'], ['Feel The Fate', 'Disc-1'], ['will be there 〜恋心', 'Disc-1'], ['Somewhere in Time', 'Disc-1'], ['Graduation', 'Disc-1'], ['Another Days', 'Disc-1'], ['Best of My Love', 'Disc-1'], ['Baby Maybe', 'Disc-1'], ['Love is message', 'Disc-1'], ['Long Road', 'Disc-1'], ['Love Train', 'Disc-1'], ['Deny', 'Disc-1'], ['Pieces', 'Disc-1'], ['四季', 'Disc-1'],
                    ['夢の場所へ', 'Disc-2'], ['Perfect Day', 'Disc-2'], ['変わりゆく空', 'Disc-2'], ['夏空の恋の詩', 'Disc-2'], ['ageha', 'Disc-2'], ['十六夜の月', 'Disc-2'], ['約束のカケラ', 'Disc-2'], ['蝉時雨', 'Disc-2'], ['TRIAL', 'Disc-2'], ['ハナムケ', 'Disc-2'], ['アメあと', 'Disc-2'], ['Everyday', 'Disc-2'], ['Be As One', 'Disc-2'], ['NOTHING IS GONNA CHANGE IT', 'Disc-2'],
                ],
            ],
            [
                'title' => 'w-inds. Best Album 20XX "THE BEST"',
                'date' => '2021-03-14',
                'best' => true,
                'tracks' => [
                    ['Forever Memories', 'Disc-1'], ['Feel The Fate', 'Disc-1'], ['Paradox', 'Disc-1'], ['try your emotion', 'Disc-1'], ['Another Days', 'Disc-1'], ['Because of you', 'Disc-1'], ['NEW PARADISE', 'Disc-1'], ['SUPER LOVER 〜I need you tonight〜', 'Disc-1'], ['Love is message', 'Disc-1'], ['Long Road', 'Disc-1'], ['Pieces', 'Disc-1'], ['キレイだ', 'Disc-1'], ['四季', 'Disc-1'], ['夢の場所へ', 'Disc-1'], ['変わりゆく空', 'Disc-1'],
                    ['十六夜の月', 'Disc-2'], ['約束のカケラ', 'Disc-2'], ['IT’S IN THE STARS', 'Disc-2'], ['TRIAL', 'Disc-2'], ['ブギウギ66', 'Disc-2'], ['ハナムケ', 'Disc-2'], ['LOVE IS THE GREATEST THING', 'Disc-2'], ['Beautiful Life', 'Disc-2'], ['アメあと', 'Disc-2'], ['Everyday', 'Disc-2'], ['CAN’T GET BACK', 'Disc-2'], ['Rain Is Fallin’', 'Disc-2'], ['HYBRID DREAM', 'Disc-2'], ['New World', 'Disc-2'], ['Truth 〜最後の真実〜', 'Disc-2'], ['Addicted to love', 'Disc-2'],
                    ['Be As One', 'Disc-3'], ['Let’s get it on', 'Disc-3'], ['You & I', 'Disc-3'], ['FLY HIGH', 'Disc-3'], ['A Little Bit', 'Disc-3'], ['夢で逢えるのに 〜Sometimes I Cry〜', 'Disc-3'], ['FANTASY', 'Disc-3'], ['In Love With The Music', 'Disc-3'], ['Boom Word Up', 'Disc-3'], ['Backstage', 'Disc-3'], ['We Don’t Need To Talk Anymore', 'Disc-3'], ['Time Has Gone', 'Disc-3'], ['Dirty Talk', 'Disc-3'], ['Get Down', 'Disc-3'], ['DoU', 'Disc-3'], ['Beautiful Now', 'Disc-3'],
                ],
            ],
        ];
    }

    private function singlesData(): array
    {
        return [
            ['title' => 'Forever Memories', 'date' => '2001-03-14', 'tracks' => ['Forever Memories', 'Moon Clock', 'Forever Memories (Za Downtown Street Remix)', 'Forever Memories (Instrumental)']],
            ['title' => 'Feel The Fate', 'date' => '2001-07-04', 'tracks' => ['Feel The Fate', 'will be there 〜恋心', 'Feel The Fate (ZA DOWNTOWN GROOVE-MASTER REMIX)', 'Feel The Fate (Instrumental)']],
            ['title' => 'Paradox', 'date' => '2001-10-17', 'tracks' => ['Paradox', 'Somewhere in Time', 'Paradox 〜ZA DOWNTOWN STREET RUMOR REMIX〜', 'Paradox 〜Instrumental〜']],
            ['title' => 'try your emotion', 'date' => '2002-02-20', 'tracks' => ['try your emotion', 'Graduation', 'try your emotion 〜MoFO★NARUSE Remix〜', 'try your emotion 〜Instrumental〜']],
            ['title' => 'Another Days', 'date' => '2002-05-22', 'tracks' => ['Another Days', 'Show me your style', 'Another Days 〜Another side mix〜', 'Another Days 〜Instrumental〜']],
            ['title' => 'Because of you', 'date' => '2002-08-21', 'tracks' => ['Because of you', 'close to you', 'Because of you 〜j\'adore party style〜', 'Because of you (Instrumental)']],
            ['title' => 'NEW PARADISE', 'date' => '2002-11-13', 'tracks' => ['NEW PARADISE', 'Best of My Love', 'NEW PARADISE 〜CANDY Future remix〜', 'NEW PARADISE 〜Instrumental〜']],
            ['title' => 'SUPER LOVER 〜I need you tonight〜', 'date' => '2003-05-21', 'tracks' => ['SUPER LOVER 〜I need you tonight〜', 'no one else', 'SUPER LOVER 〜I need you tonight〜 (Instrumental)', 'no one else (Instrumental)']],
            ['title' => 'Love is message', 'date' => '2003-08-20', 'tracks' => ['Love is message', 'Night Flight 〜夜間飛行〜', 'Love is message (Instrumental)', 'Night Flight 〜夜間飛行〜 (Instrumental)']],
            ['title' => 'Long Road', 'date' => '2003-10-29', 'tracks' => ['Long Road', 'NEVER MIND', 'Long Road (Instrumental)', 'NEVER MIND (Instrumental)']],
            ['title' => 'Pieces', 'date' => '2004-03-10', 'tracks' => ['Pieces', 'move your body', 'Pieces (Instrumental)', 'move your body (Instrumental)']],
            ['title' => 'キレイだ', 'date' => '2004-06-02', 'tracks' => ['キレイだ', 'ふたりがふたりで', 'キレイだ (Instrumental)', 'ふたりがふたりで (Instrumental)']],
            ['title' => '四季', 'date' => '2004-10-06', 'tracks' => ['四季', '永遠の途中', '四季 (Instrumental)', '永遠の途中 (Instrumental)']],
            ['title' => '夢の場所へ', 'date' => '2005-01-01', 'tracks' => ['夢の場所へ', 'Perfect day', '夢の場所へ (Instrumental)', 'Perfect day (Instrumental)']],
            ['title' => '変わりゆく空', 'date' => '2005-03-16', 'tracks' => ['変わりゆく空', 'いつか、虹の下で', '変わりゆく空 (Instrumental)', 'いつか、虹の下で (Instrumental)']],
            ['title' => '十六夜の月', 'date' => '2005-08-31', 'tracks' => ['十六夜の月', 'ジレンマ', '十六夜の月 (Instrumental)', 'ジレンマ (Instrumental)']],
            ['title' => '約束のカケラ', 'date' => '2005-11-23', 'tracks' => ['約束のカケラ', 'デジャヴ', 'Pearl Dance', '約束のカケラ (Instrumental)', 'デジャヴ (Instrumental)', 'Pearl Dance (Instrumental)']],
            ['title' => 'IT’S IN THE STARS', 'date' => '2006-02-22', 'tracks' => ['IT’S IN THE STARS', 'Philosophy', 'Special Thanx!', 'IT’S IN THE STARS (Instrumental)']],
            ['title' => 'TRIAL', 'date' => '2006-05-24', 'tracks' => ['TRIAL', 'Back At One', '風詩 -KAZAUTA-', 'TRIAL (Instrumental)']],
            ['title' => 'ブギウギ66', 'date' => '2006-09-06', 'tracks' => ['ブギウギ66', 'Drive-Me-Crazy', 'If…', 'ブギウギ66 (Instrumental)']],
            ['title' => 'ハナムケ', 'date' => '2007-01-17', 'tracks' => ['ハナムケ', 'Want ya', '勿忘草', 'ハナムケ (Instrumental)']],
            ['title' => 'LOVE IS THE GREATEST THING', 'date' => '2007-07-04', 'tracks' => ['LOVE IS THE GREATEST THING', 'SHINING STAR', '夏祭り', 'LOVE IS THE GREATEST THING (Instrumental)']],
            ['title' => 'Beautiful Life', 'date' => '2007-11-07', 'tracks' => ['Beautiful Life', 'Space Drifter', 'I’m a Man', 'Beautiful Life (Instrumental)']],
            ['title' => 'アメあと', 'date' => '2008-04-23', 'tracks' => ['アメあと', 'One Love', 'leave me alone', 'アメあと (Instrumental)']],
            ['title' => 'Everyday/CAN’T GET BACK', 'date' => '2008-11-26', 'tracks' => ['Everyday', 'CAN’T GET BACK', 'Color', 'YES or NO', 'Everyday (Instrumental)', 'CAN’T GET BACK (Instrumental)']],
            ['title' => 'Rain Is Fallin’/HYBRID DREAM', 'date' => '2009-05-13', 'tracks' => ['Rain Is Fallin’', 'HYBRID DREAM', 'Upside Down', 'You are…', 'Rain Is Fallin’ (Instrumental)', 'HYBRID DREAM (Instrumental)']],
            ['title' => 'New World/Truth〜最後の真実〜', 'date' => '2009-12-09', 'tracks' => ['New World', 'Truth 〜最後の真実〜', 'Fighting For Love', 'Tribute', 'New World (Radio Mix)']],
            ['title' => 'Addicted to love', 'date' => '2010-06-23', 'tracks' => ['Addicted to love', 'Love or Leave', 'Now You’re Gone', 'Rain', 'Addicted to love (Instrumental)']],
            ['title' => 'Be As One/Let’s get it on', 'date' => '2011-01-26', 'tracks' => ['Be As One', 'Let’s get it on', 'Noise', 'To My Fans']],
            ['title' => 'You & I', 'date' => '2011-08-17', 'tracks' => ['You & I', 'Chillin’ in the Daydream', 'Humanizer', 'I vs. I']],
            ['title' => 'FLY HIGH', 'date' => '2012-02-22', 'tracks' => ['FLY HIGH', 'Put your hands up!!!', 'Zirconia 〜ジルコニア〜', 'More than words']],
            ['title' => 'A Little Bit', 'date' => '2013-10-30', 'tracks' => ['A Little Bit', 'Rock Your Body', 'Tell Me What You’re Waiting For', 'We’ll Be Alright']],
            ['title' => '夢で逢えるのに 〜Sometimes I Cry〜', 'date' => '2014-06-11', 'tracks' => ['夢で逢えるのに 〜Sometimes I Cry〜', 'Turned up', 'Together Now', 'Say so long']],
            ['title' => 'FANTASY', 'date' => '2015-01-21', 'tracks' => ['FANTASY', 'Million Dollar Girl', 'Frozen in my heart', 'Sweetest love']],
            ['title' => 'In Love With The Music', 'date' => '2015-06-10', 'tracks' => ['In Love With The Music', 'HEADS UP', 'Ring Off The Hook', 'Sail away']],
            ['title' => 'Boom Word Up', 'date' => '2016-05-03', 'tracks' => ['Boom Word Up', 'Smile Smile Smile', 'ヒマワリ', 'FUNTIME']],
            ['title' => 'Backstage', 'date' => '2016-08-31', 'tracks' => ['Backstage', 'Treasure', 'Drop Drop', 'No matter where you are']],
            ['title' => 'We Don’t Need To Talk Anymore', 'date' => '2017-01-11', 'tracks' => ['We Don’t Need To Talk Anymore', 'Again']],
            ['title' => 'Time Has Gone', 'date' => '2017-09-27', 'tracks' => ['Time Has Gone', 'This Love', 'A Trip In My Hard Days']],
            ['title' => 'Dirty Talk', 'date' => '2018-03-14', 'tracks' => ['Dirty Talk', 'If I said I loved you']],
            ['title' => 'Get Down', 'date' => '2019-07-31', 'tracks' => ['Get Down', 'Take It Slow', 'Femme Fatale']],
            ['title' => 'DoU', 'date' => '2020-01-22', 'tracks' => ['DoU', 'CANDY', 'We Don’t Need To Talk Anymore Remix feat.SKY-HI']],
            ['title' => 'Beautiful Now', 'date' => '2020-12-02', 'download' => true, 'tracks' => ['Beautiful Now']],
            ['title' => 'Strip', 'date' => '2021-09-24', 'download' => true, 'tracks' => ['Strip']],
            ['title' => 'Little', 'date' => '2021-10-22', 'download' => true, 'tracks' => ['Little']],
            ['title' => 'The Christmas Song (feat. DA PUMP & Lead)', 'date' => '2021-11-19', 'download' => true, 'tracks' => ['The Christmas Song (feat. DA PUMP & Lead)']],
            ['title' => 'Bang! Bang! (feat. CrazyBoy)', 'date' => '2022-12-28', 'download' => true, 'tracks' => ['Bang! Bang! (feat. CrazyBoy)']],
            ['title' => 'Run', 'date' => '2023-09-22', 'download' => true, 'tracks' => ['Run']],
            ['title' => 'FAKE IT', 'date' => '2024-02-14', 'download' => true, 'tracks' => ['FAKE IT']],
            ['title' => 'Imagination', 'date' => '2024-05-01', 'download' => true, 'tracks' => ['Imagination']],
            ['title' => 'Who’s the Liar', 'date' => '2025-02-26', 'download' => true, 'tracks' => ['Who’s the Liar']],
        ];
    }
}
