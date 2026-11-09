<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

// 宇多田ヒカル（Utada 名義を含む）のシングル・アルバムを取り込む。曲（DbSong）は登録済みなので、曲名でつなぐ（新しい曲は作らない）。
// 出典は ja.wikipedia.org「宇多田ヒカルの作品」と各作品の記事（2026-11-09）。U3・Cubic U 名義、トリビュート盤、映像作品（DVDシングルを含む）は入れない。
// - CDシングルには通し番号（single_id 1〜22）、配信シングル・Utada 名義のシングルには番号なし。スタジオ・アルバムは album_id 1〜8
// - Utada 名義の作品は、説明（text）に「Utada 名義」と書き、アルバムは番号の付かない企画盤（special）にする（松本孝弘の取り込みと同じ）
// - カラオケ・Instrumental・A Cappella・リミックス・Dub・インタールードはリンクなし（曲名だけ）。曲を [曲名, null] で表す
// - 別バージョン（Album Version・Mix・Re-Recording・Remastered・英語版・Live Version 等）は元の曲につなぎ、曲名を exception に付ける。
//   英語版はセトリの別表記と同じ考え方で元の曲（Simple And Clean → 光、Sanctuary → Passion、Don't Think Twice → 誓い）
// - 8cm と 12cm で曲が違うシングルは、8cm の曲順のあとに 12cm だけの曲を足す
// - 何度流しても同じになる（作品は日付と題名で見つけて上書き。題名を直したときは、同じ日の前の書き方の作品を消す）
class ImportUtadaDiscography extends Command
{
    protected $signature = 'import:utada-discography {--dry-run : Show matching without saving}';

    protected $description = 'Import Hikaru Utada (and Utada) singles and albums, matching tracks to existing DbSong records';

    private const ARTIST_ID = 56;

    private const UTADA = 'Utada 名義';

    // 入れないことにした配信シングル（日付 => 題名）。前の取り込みで入っていたら消す
    private const REMOVED_SINGLES = [
        ['2017-01-11', '光 -Ray Of Hope MIX-'],
        ['2018-11-06', 'Too Proud featuring XZT, Suboi, EK (L1 Remix)'],
        ['2023-02-17', '40代はいろいろ -Live from Metropolis Studios-'],
        ['2023-08-25', "Gold 〜また逢う日まで〜 (Taku's Twice Upon a Time Remix)"],
        ['2025-02-15', 'Electricity (Karen Nyame KG Remix)'],
        ['2025-02-28', 'Electricity (Arca Remix)'],
        ['2025-03-31', 'Electricity (salute Remix)'],
        ['2025-10-06', 'Mine or Yours (Yaeji Remix)'],
        ['2025-10-20', 'Mine or Yours (The Blessed Madonna’s GODSQUAD Mix)'],
        ['2025-11-07', 'Mine or Yours (Bella Boo Remix)'],
    ];

    private function normalize(string $title): string
    {
        $title = mb_convert_kana($title, 'as');
        $title = str_replace(['’', '‘', '´', '“', '”'], ["'", "'", "'", '"', '"'], $title);

        return mb_strtolower(preg_replace('/[\s・～〜~\-‐ー]+/u', '', $title), 'UTF-8');
    }

    /**
     * トラック1つ → tracklist の要素。$track は '曲名'（DbSong の曲名と同じなら、そのままリンク）か、
     * [表示する曲名, つなぐ DbSong の曲名 | null（リンクしない）]
     */
    private function trackItem($track, array $idByTitle, array &$unmatched, string $release, ?string $disc): ?array
    {
        [$label, $songTitle] = is_array($track) ? $track : [$track, $track];
        $item = [];
        if ($songTitle === null) {
            $item['exception'] = $label;
        } else {
            $id = $idByTitle[$this->normalize($songTitle)] ?? null;
            if ($id === null) {
                $unmatched[] = "{$release} / {$label}";
                return null;
            }
            $item['id'] = (string) $id;
            if (is_array($track) && $label !== $songTitle) {
                $item['exception'] = $label;
            }
        }
        if ($disc !== null) {
            $item['disc'] = $disc;
        }

        return $item;
    }

    private function tracklist(array $release, array $idByTitle, array &$unmatched): array
    {
        $groups = $release['discs'] ?? [null => $release['tracks']];
        $items = [];
        foreach ($groups as $disc => $tracks) {
            foreach ($tracks as $track) {
                if ($item = $this->trackItem($track, $idByTitle, $unmatched, $release['title'], $disc === '' ? null : $disc)) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $idByTitle = [];
        foreach (DbSong::where('artist_id', self::ARTIST_ID)->get(['id', 'title']) as $song) {
            $idByTitle[$this->normalize($song->title)] = $song->id;
        }
        $unmatched = [];

        foreach ($this->singlesData() as $s) {
            $tracks = $this->tracklist($s, $idByTitle, $unmatched);
            $kind = ($s['ep'] ?? false) ? 'EP' : (($s['download'] ?? false) ? 'digital' : 'single');
            $this->line(sprintf('[%-7s] %s %s%s (%d)', $kind, $s['date'], isset($s['num']) ? "#{$s['num']} " : '', $s['title'], count($tracks)));
            if (!$dryRun) {
                $saved = DbSingle::updateOrCreate(
                    ['artist_id' => self::ARTIST_ID, 'date' => $s['date'], 'title' => $s['title']],
                    ['single_id' => $s['num'] ?? null, 'download' => $s['download'] ?? false, 'ep' => $s['ep'] ?? false, 'text' => $s['text'] ?? null, 'tracklist' => $tracks]
                );
                $this->removeOldTitles(DbSingle::class, $saved);
            }
        }

        foreach ($this->albumsData() as $a) {
            $tracks = $this->tracklist($a, $idByTitle, $unmatched);
            $this->line(sprintf('[album  ] %s %s%s (%d)', $a['date'], isset($a['num']) ? "#{$a['num']} " : '', $a['title'], count($tracks)));
            if (!$dryRun) {
                $saved = DbAlbum::updateOrCreate(
                    ['artist_id' => self::ARTIST_ID, 'date' => $a['date'], 'title' => $a['title']],
                    ['album_id' => $a['num'] ?? null, 'best' => $a['best'] ?? false, 'mini' => false, 'special' => $a['special'] ?? false, 'text' => $a['text'] ?? null, 'tracklist' => $tracks]
                );
                $this->removeOldTitles(DbAlbum::class, $saved);
            }
        }

        foreach (self::REMOVED_SINGLES as [$date, $title]) {
            $old = DbSingle::where('artist_id', self::ARTIST_ID)->where('date', $date)->where('title', $title)->first();
            if ($old) {
                $this->line("remove: {$date} {$title}");
                if (!$dryRun) {
                    $old->delete();
                }
            }
        }

        foreach ($unmatched as $u) {
            $this->warn("unmatched: {$u}");
        }
        $this->info($dryRun ? 'Dry run complete.' : 'Import complete.');
    }

    // 決まり11：書き方を直して題名が変わったとき、同じ日の前の書き方の作品が残らないように消す
    private function removeOldTitles(string $model, $saved): void
    {
        foreach ($model::where('artist_id', self::ARTIST_ID)->where('date', $saved->date)->whereKeyNot($saved->id)->get() as $old) {
            if ($this->normalize($old->title) === $this->normalize($saved->title)) {
                $this->line("remove old: {$old->title}");
                $old->delete();
            }
        }
    }

    private function singlesData(): array
    {
        $plain = fn ($title) => [$title, null];
        $digital = fn ($title, $date, $tracks = null, $num = null) => ['title' => $title, 'date' => $date, 'num' => $num, 'download' => true, 'tracks' => $tracks ?? [$title]];

        return [
            // ===== CDシングル =====
            ['num' => '1', 'title' => 'Automatic / time will tell', 'date' => '1998-12-09', 'tracks' => ['Automatic', 'time will tell', $plain('Automatic (Original Karaoke)'), $plain('time will tell -DUB MIX-')]],
            ['num' => '2', 'title' => "Movin' on without you", 'date' => '1999-02-17', 'tracks' => ["Movin' on without you", 'B & C', $plain("Movin' on without you -original karaoke-"), $plain("Movin' on without you -tribal mix-")]],
            ['num' => '3', 'title' => 'First Love', 'date' => '1999-04-28', 'tracks' => [
                'First Love', ['First Love -Strings Mix', 'First Love'], $plain('First Love -Original Karaoke'), $plain('First Love -John Luongo Remix'),
                ['First Love -featuring David Sanborn', 'First Love'],
            ]],
            ['num' => '4', 'title' => 'Addicted To You', 'date' => '1999-11-10', 'tracks' => [
                ['Addicted To You (UP-IN-HEAVEN MIX)', 'Addicted To You'], ['Addicted To You (UNDERWATER MIX)', 'Addicted To You'],
                $plain('Addicted To You (UP-IN-HEAVEN MIX)(Instrumental)'), $plain('Addicted To You (UNDERWATER MIX)(Instrumental)'),
            ]],
            ['num' => '5', 'title' => 'Wait & See 〜リスク〜', 'date' => '2000-04-19', 'tracks' => [
                'Wait & See 〜リスク〜', 'はやとちり', 'Fly Me To The Moon (In Other Words)', $plain('Wait & See 〜リスク〜 (Baton Girl Mix)'), $plain('Wait & See 〜リスク〜 (ORIGINAL KARAOKE)'),
            ]],
            ['num' => '6', 'title' => 'For You / タイム・リミット', 'date' => '2000-06-30', 'tracks' => ['For You', 'タイム・リミット', $plain('For You (Original Karaoke)'), $plain('タイム・リミット (Original Karaoke)')]],
            ['num' => '7', 'title' => 'Can You Keep A Secret?', 'date' => '2001-02-16', 'tracks' => ['Can You Keep A Secret?', '蹴っ飛ばせ!', $plain('Can You Keep A Secret? (Original Karaoke)')]],
            ['num' => '8', 'title' => 'FINAL DISTANCE', 'date' => '2001-07-25', 'tracks' => [
                'FINAL DISTANCE', $plain('DISTANCE -PLANITb Remix-'), $plain('DISTANCE -m-flo remix-'), 'DISTANCE', $plain('DISTANCE -Original Karaoke-'), $plain('FINAL DISTANCE -Instrumental-'),
            ]],
            ['num' => '9', 'title' => 'traveling', 'date' => '2001-11-28', 'tracks' => ['traveling', $plain('traveling -PLANITb remix-'), $plain('traveling -Bahiatronic Mix-'), $plain('traveling -Original Karaoke-')]],
            ['num' => '10', 'title' => '光', 'date' => '2002-03-20', 'tracks' => ['光', $plain('光 -PLANITb Remix-'), $plain('光 -Godson Mix-'), $plain('光 -Original Karaoke-')]],
            ['num' => '11', 'title' => 'SAKURAドロップス / Letters', 'date' => '2002-05-09', 'tracks' => ['SAKURAドロップス', 'Letters', $plain('SAKURAドロップス -Original Karaoke-'), $plain('Letters -Original Karaoke-')]],
            ['num' => '12', 'title' => 'COLORS', 'date' => '2003-01-29', 'tracks' => ['COLORS', ['Simple And Clean', '光'], $plain('Simple And Clean -PLANITb Remix-'), $plain('COLORS -Original Karaoke-')]],
            ['num' => '13', 'title' => '誰かの願いが叶うころ', 'date' => '2004-04-21', 'tracks' => ['誰かの願いが叶うころ', $plain('誰かの願いが叶うころ (Instrumental)')]],
            ['num' => '14', 'title' => 'Be My Last', 'date' => '2005-09-28', 'tracks' => ['Be My Last']],
            ['num' => '15', 'title' => 'Passion', 'date' => '2005-12-14', 'tracks' => [['Passion 〜single version〜', 'Passion'], ['Passion 〜after the battle〜', 'Passion']]],
            ['num' => '16', 'title' => "Keep Tryin'", 'date' => '2006-02-22', 'tracks' => ["Keep Tryin'", 'WINGS', $plain("Keep Tryin' -Original Karaoke-")]],
            ['num' => '17', 'title' => 'ぼくはくま', 'date' => '2006-11-22', 'tracks' => ['ぼくはくま', $plain('ぼくはくま (オリジナル・カラオケ)')]],
            ['num' => '18', 'title' => 'Flavor Of Life', 'date' => '2007-02-28', 'tracks' => [
                'Flavor Of Life', ['Flavor Of Life -Ballad Version-', 'Flavor Of Life'], $plain('Flavor Of Life (Original Karaoke)'),
                $plain('Flavor Of Life -Ballad Version- (Original Karaoke)'), $plain('Flavor Of Life -Antidote Mix-'),
            ]],
            ['num' => '19', 'title' => 'Beautiful World / Kiss & Cry', 'date' => '2007-08-29', 'tracks' => [
                'Beautiful World', 'Kiss & Cry', ['Fly Me To The Moon (In Other Words) -2007 MIX-', 'Fly Me To The Moon (In Other Words)'],
                $plain('Beautiful World [Original Karaoke]'), $plain('Kiss & Cry [Original Karaoke]'), $plain('Fly Me To The Moon (In Other Words) -2007 MIX- [Original Karaoke]'),
            ]],
            ['num' => '20', 'title' => 'HEART STATION / Stay Gold', 'date' => '2008-02-20', 'tracks' => ['HEART STATION', 'Stay Gold', $plain('HEART STATION Original Karaoke'), $plain('Stay Gold Original Karaoke')]],
            ['num' => '21', 'title' => 'Prisoner Of Love', 'date' => '2008-05-21', 'tracks' => [
                'Prisoner Of Love', ['Prisoner Of Love -Quiet Version-', 'Prisoner Of Love'], $plain('Prisoner Of Love (Original Karaoke)'), $plain('Prisoner Of Love -Quiet Version- (Original Karaoke)'),
            ]],
            ['num' => '22', 'title' => 'Face My Fears', 'date' => '2019-01-18', 'tracks' => [
                ['Face My Fears (Japanese Version)', 'Face My Fears'], '誓い', ['Face My Fears (English Version)', 'Face My Fears'], ["Don't Think Twice", '誓い'],
            ]],

            // ===== 配信限定シングル =====
            $digital('桜流し', '2012-11-17'),
            $digital('花束を君に', '2016-04-15'),
            $digital('真夏の通り雨', '2016-04-15'),
            $digital('大空で抱きしめて', '2017-07-10'),
            $digital('Forevermore', '2017-07-28'),
            $digital('あなた', '2017-12-08'),
            $digital('Play A Love Song', '2018-04-25'),
            $digital('初恋', '2018-05-30'),
            $digital('Time', '2020-05-08'),
            $digital('誰にも言わない', '2020-05-29'),
            $digital('PINK BLOOD', '2021-06-02'),
            $digital('君に夢中', '2021-11-26'),
            $digital('Gold 〜また逢う日まで〜', '2023-07-28'),
            $digital('何色でもない花', '2024-02-12'),
            $digital('Mine or Yours', '2025-05-02'),
            $digital('First Love - From THE FIRST TAKE', '2025-06-02', [['First Love - From THE FIRST TAKE', 'First Love']]),
            $digital('パッパパラダイス', '2026-05-06'),

            // ===== その他の配信シングル（アルバムの先行配信・別バージョン）。リミックスだけの配信と「40代はいろいろ」（ライブ音源）は入れない =====
            $digital('This Is Love', '2006-05-31'),
            $digital('Kiss & Cry', '2007-05-31'),
            $digital('Fly Me To The Moon (In Other Words) -2007 MIX-', '2007-07-04', [['Fly Me To The Moon (In Other Words) -2007 MIX-', 'Fly Me To The Moon (In Other Words)']]),
            $digital('Eternally -Drama Mix-', '2008-10-31', [['Eternally -Drama Mix-', 'Eternally']]),
            $digital('Beautiful World -PLANiTb Acoustica Mix-', '2009-06-27', [['Beautiful World -PLANiTb Acoustica Mix-', 'Beautiful World']]),
            $digital('Hymne à l\'amour 〜愛のアンセム〜', '2010-09-28'),
            $digital('Goodbye Happiness', '2010-11-10'),
            $digital('Show Me Love (Not A Dream)', '2010-11-17'),
            $digital('道', '2016-09-16'),
            $digital('二時間だけのバカンス featuring 椎名林檎', '2016-09-16', [['二時間だけのバカンス featuring 椎名林檎', '二時間だけのバカンス']]),
            $digital('誓い', '2018-12-10'),
            $digital("Don't Think Twice", '2018-12-10', [["Don't Think Twice", '誓い']]),
            $digital('One Last Kiss', '2021-03-09'),
            $digital('First Love (2022 Mix)', '2022-12-09', [['First Love (2022 Mix)', 'First Love']]),
            $digital('初恋 (2022 Remastering)', '2022-12-09', [['初恋 (2022 Remastering)', '初恋']]),
            $digital('Automatic (2024 Mix)', '2024-03-22', [['Automatic (2024 Mix)', 'Automatic']]),
            $digital('Somewhere Near Marseilles -マルセイユ辺り- (Sci-Fi Edit)', '2024-04-03', [['Somewhere Near Marseilles -マルセイユ辺り- (Sci-Fi Edit)', 'Somewhere Near Marseilles ーマルセイユ辺りー']]),
            $digital('Simple And Clean (Re-Recording)', '2024-05-22', [['Simple And Clean (Re-Recording)', '光']]),

            // ===== その他のシングル（アナログ盤） =====
            ['title' => 'REMIX: Fly Me To The Moon', 'date' => '2000-05-10', 'tracks' => [
                $plain('Fly Me To The Moon -Mike Brown Remix'), $plain('Fly Me To The Moon -Jaames McQuade Remix'), $plain("Fly Me To The Moon -VC2's Club Mix"), $plain('Fly Me To The Moon -Bob Allecca Remix'),
            ]],
            ['title' => 'First Love / 初恋', 'date' => '2022-12-09', 'tracks' => [
                ['First Love (2022 Mix)', 'First Love'], $plain('First Love (A Cappella Mix)'), ['初恋 (2022 Remastering)', '初恋'], $plain('初恋 (A Cappella Mix)'),
            ]],

            // ===== EP =====
            ['title' => 'One Last Kiss', 'date' => '2021-03-10', 'ep' => true, 'tracks' => [
                'One Last Kiss', ['Beautiful World (Da Capo Version)', 'Beautiful World'], ['Beautiful World -2021 Remastered-', 'Beautiful World'],
                ['Beautiful World (PLANiTb Acoustica Mix) -2021 Remastered-', 'Beautiful World'], ['桜流し -2021 Remastered-', '桜流し'],
                ['Fly Me To The Moon (In Other Words) (2007 MIX) -2021 Remastered-', 'Fly Me To The Moon (In Other Words)'],
                $plain('One Last Kiss (Instrumental)'), $plain('Beautiful World (Da Capo Version) (Instrumental)'),
            ]],
            ['title' => 'Gold 〜また逢う日まで〜 EP', 'date' => '2023-09-13', 'ep' => true, 'download' => true, 'tracks' => [
                'Gold 〜また逢う日まで〜', $plain('Gold 〜また逢う日まで〜 (Instrumental)'), $plain('Gold 〜また逢う日まで〜 (Taku\'s Twice Upon a Time Remix)'),
                $plain('Gold 〜また逢う日まで〜 (Taku\'s Twice Upon a Time Remix) (Instrumental)'), $plain('Gold 〜また逢う日まで〜 (Taku\'s Twice Upon a Time Remix) (ALT Intro)'),
            ]],
            ['title' => 'Electricity Remixes', 'date' => '2025-07-09', 'ep' => true, 'download' => true, 'tracks' => [
                ['Electricity (Original track)', 'Electricity'], $plain('Electricity (Karen Nyame KG Remix)'), $plain('Electricity (Arca Remix)'), $plain('Electricity (salute Remix)'),
                $plain('Electricity (Original track) -Instrumental-'),
            ]],
            ['title' => 'Mine or Yours', 'date' => '2025-11-26', 'ep' => true, 'tracks' => [
                'Mine or Yours', $plain('Mine or Yours (Yaeji Remix)'), $plain('Mine or Yours (The Blessed Madonna’s GODSQUAD Mix)'), $plain('Mine or Yours (Bella Boo Remix)'),
                ['Mine or Yours - from THE FIRST TAKE', 'Mine or Yours'], $plain('Mine or Yours (Instrumental)'),
            ]],
            ['title' => 'パッパパラダイス', 'date' => '2026-06-24', 'ep' => true, 'tracks' => [
                ['パッパパラダイス feat. 甲本ヒロト', 'パッパパラダイス'], 'パッパパラダイス', $plain('パッパパラダイス (Instrumental)'),
            ]],

            // ===== Utada 名義のシングル =====
            ['title' => 'Devil Inside', 'date' => '2004-09-14', 'text' => self::UTADA, 'tracks' => [
                ['Devil Inside (Album Version)', 'Devil Inside'], $plain('Devil Inside (RJD2 Remix)'), $plain('Devil Inside (Richard Vission Radio Edit)'), $plain('Devil Inside (The Scumfrog Radio Edit)'),
                $plain('Devil Inside (Richard Vission Experience)'), $plain('Devil Inside (Richard Vission Duberience)'), $plain('Devil Inside (The Scum Frog Vocal Mix)'), $plain('Devil Inside (The Scum Frog Dub)'),
            ]],
            ['title' => "Exodus '04", 'date' => '2005-06-21', 'text' => self::UTADA, 'tracks' => [
                $plain("Exodus '04 (Double J Radio Mix)"), $plain("Exodus '04 (Josh Harris' Elektrik Radio)"), $plain("Exodus '04 (Josh Harris vs. The MPC Radio)"),
                $plain("Exodus '04 (Josh Harris' Exodus Experience)"), $plain("Exodus '04 (Double J Extended Mix)"), $plain("Exodus '04 (Peter Bailey Dub)"), $plain("Exodus '04 (JJ Flores Dub #1)"),
                $plain("Exodus '04 (Kriya vs. Velez Progressive Trance Mix)"), $plain("Exodus '04 (Kriya vs. Velez Electro House Mix)"), $plain("Exodus '04 (JJ Flores Dub #2)"),
            ]],
            ['title' => 'You Make Me Want To Be A Man', 'date' => '2005-10-17', 'text' => self::UTADA, 'tracks' => [
                ['You Make Me Want To Be a Man -Radio Edit-', 'You Make Me Want To Be A Man'], $plain('You Make Me Want To Be a Man -Junior Jack Mix-'), $plain('You Make Me Want To Be a Man -Bloodshy and Avant Mix-'),
            ]],
            ['title' => 'Come Back To Me', 'date' => '2009-02-10', 'download' => true, 'text' => self::UTADA, 'tracks' => ['Come Back to Me']],
            ['title' => 'Sanctuary (Opening)', 'date' => '2009-07-22', 'download' => true, 'text' => self::UTADA, 'tracks' => [['Sanctuary (Opening)', 'Passion']]],
            ['title' => 'Sanctuary (Ending)', 'date' => '2009-07-22', 'download' => true, 'text' => self::UTADA, 'tracks' => [['Sanctuary (Ending)', 'Passion']]],
            ['title' => 'Dirty Desire', 'date' => '2009-12-21', 'download' => true, 'text' => self::UTADA, 'tracks' => ['Dirty Desire']],
        ];
    }

    private function albumsData(): array
    {
        $plain = fn ($title) => [$title, null];

        return [
            // ===== スタジオ・アルバム =====
            ['num' => '1', 'title' => 'First Love', 'date' => '1999-03-10', 'tracks' => [
                ['Automatic -Album Edit-', 'Automatic'], "Movin' on without you", 'In My Room', 'First Love', '甘いワナ ~Paint It, Black', 'time will tell', 'Never Let Go',
                ['B&C -Album Version-', 'B & C'], 'Another Chance', $plain('Interlude'), 'Give Me A Reason', $plain('Automatic -Johnny Vicious Remix-'),
            ]],
            ['num' => '2', 'title' => 'Distance', 'date' => '2001-03-28', 'tracks' => [
                'Wait & See 〜リスク〜', 'Can You Keep A Secret?', 'DISTANCE', 'サングラス', 'ドラマ', 'Eternally', ['Addicted To You (UP-IN-HEAVEN MIX)', 'Addicted To You'],
                'For You', '蹴っ飛ばせ!', 'Parody', 'タイム・リミット', '言葉にならない気持ち', $plain('HAYATOCHI-REMIX'),
            ]],
            ['num' => '3', 'title' => 'DEEP RIVER', 'date' => '2002-06-19', 'tracks' => [
                'SAKURAドロップス', 'traveling', '幸せになろう', 'Deep River', 'Letters', 'プレイ・ボール', '東京NIGHTS', 'A.S.A.P.', '嘘みたいな I Love You', 'FINAL DISTANCE',
                $plain('Bridge (Interlude)'), '光',
            ]],
            ['num' => '4', 'title' => 'ULTRA BLUE', 'date' => '2006-06-14', 'tracks' => [
                'This Is Love', "Keep Tryin'", 'BLUE', '日曜の朝', 'Making Love', '誰かの願いが叶うころ', 'COLORS', ['One Night Magic feat. Yamada Masashi', 'One Night Magic'], '海路', 'WINGS',
                'Be My Last', 'Eclipse (Interlude)', 'Passion', ['Sanctuary -Opening-', 'Passion'],
            ]],
            ['num' => '5', 'title' => 'HEART STATION', 'date' => '2008-03-19', 'tracks' => [
                'Fight The Blues', 'HEART STATION', 'Beautiful World', ['Flavor Of Life -Ballad Version-', 'Flavor Of Life'], 'Stay Gold', 'Kiss & Cry', $plain('Gentle Beast Interlude'),
                'Celebrate', 'Prisoner Of Love', 'テイク 5', 'ぼくはくま', '虹色バス', 'Flavor Of Life',
            ]],
            ['num' => '6', 'title' => 'Fantôme', 'date' => '2016-09-28', 'tracks' => [
                '道', '俺の彼女', '花束を君に', ['二時間だけのバカンス featuring 椎名林檎', '二時間だけのバカンス'], '人魚', ['ともだち with 小袋成彬', 'ともだち'], '真夏の通り雨', '荒野の狼',
                ['忘却 featuring KOHH', '忘却'], '人生最高の日', '桜流し',
            ]],
            ['num' => '7', 'title' => '初恋', 'date' => '2018-06-27', 'tracks' => [
                'Play A Love Song', 'あなた', '初恋', '誓い', 'Forevermore', ['Too Proud featuring Jevon', 'Too Proud'], 'Good Night', 'パクチーの唄', '残り香', '大空で抱きしめて', '夕凪', '嫉妬されるべき人生',
            ]],
            ['num' => '8', 'title' => 'BADモード', 'date' => '2022-01-19', 'tracks' => [
                'BADモード', '君に夢中', 'One Last Kiss', 'PINK BLOOD', 'Time', '気分じゃないの (Not In The Mood)', '誰にも言わない', 'Find Love',
                ['Face My Fears (Japanese Version)', 'Face My Fears'], 'Somewhere Near Marseilles ーマルセイユ辺りー', ['Beautiful World (Da Capo Version)', 'Beautiful World'],
                ['キレイな人 (Find Love)', 'Find Love'], ['Face My Fears (English Version)', 'Face My Fears'], $plain('Face My Fears (A.G. Cook Remix)'),
            ]],

            // ===== ベスト・アルバム =====
            ['title' => 'Utada Hikaru SINGLE COLLECTION VOL.1', 'date' => '2004-03-31', 'best' => true, 'tracks' => [
                'time will tell', 'Automatic', "Movin' on without you", 'First Love', ['Addicted To You (UP-IN-HEAVEN MIX)', 'Addicted To You'], 'Wait & See 〜リスク〜', 'For You', 'タイム・リミット',
                'Can You Keep A Secret?', 'FINAL DISTANCE', 'traveling', '光', 'SAKURAドロップス', 'Letters', 'COLORS',
            ]],
            ['title' => 'Utada Hikaru SINGLE COLLECTION VOL.2', 'date' => '2010-11-24', 'best' => true, 'discs' => [
                'Disc1' => [
                    'Prisoner Of Love', 'Stay Gold', 'HEART STATION', 'Kiss & Cry', 'Beautiful World', ['Flavor Of Life -Ballad Version-', 'Flavor Of Life'], 'ぼくはくま', 'This Is Love',
                    "Keep Tryin'", 'Passion', 'Be My Last', '誰かの願いが叶うころ', ['Beautiful World -PLANiTb Acoustica Mix-', 'Beautiful World'],
                ],
                'Disc2' => ['嵐の女神', 'Show Me Love (Not A Dream)', 'Goodbye Happiness', 'Hymne à l\'amour 〜愛のアンセム〜', "Can't Wait 'Til Christmas"],
            ]],
            ['title' => 'SCIENCE FICTION', 'date' => '2024-04-10', 'best' => true, 'discs' => [
                'Disc-1' => [
                    ['Addicted To You (Re-Recording)', 'Addicted To You'], ['First Love (2022 Mix)', 'First Love'], '花束を君に', 'One Last Kiss', ['SAKURAドロップス (2024 Mix)', 'SAKURAドロップス'],
                    'あなた', ['Can You Keep A Secret? (2024 Mix)', 'Can You Keep A Secret?'], '道', ['Prisoner Of Love (2024 Mix)', 'Prisoner Of Love'], ['光 (Re-Recording)', '光'],
                    ['Flavor Of Life -Ballad Version- (2024 Mix)', 'Flavor Of Life'], ['Goodbye Happiness (2024 Mix)', 'Goodbye Happiness'],
                ],
                'Disc-2' => [
                    ['traveling (Re-Recording)', 'traveling'], ['Beautiful World (2024 Mix)', 'Beautiful World'], ['Automatic (2024 Mix)', 'Automatic'], '君に夢中', '何色でもない花', '初恋', 'Time',
                    ['Letters (2024 Mix)', 'Letters'], 'BADモード', ['COLORS (2024 Mix)', 'COLORS'], ['二時間だけのバカンス featuring 椎名林檎', '二時間だけのバカンス'], 'Gold 〜また逢う日まで〜', 'Electricity',
                    ['Somewhere Near Marseilles -マルセイユ辺り- (Sci-Fi Edit)', 'Somewhere Near Marseilles ーマルセイユ辺りー'],
                ],
            ]],

            // ===== ライブ・アルバム（配信限定） =====
            ['title' => 'Hikaru Utada Live Sessions from Air Studios', 'date' => '2022-06-09', 'tracks' => [
                ['BADモード -Live Version-', 'BADモード'], ['One Last Kiss -Live Version-', 'One Last Kiss'], ['君に夢中 -Live Version-', '君に夢中'], ['誰にも言わない -Live Version-', '誰にも言わない'],
                ['Find Love -Live Version-', 'Find Love'], ['Time -Live Version-', 'Time'], ['PINK BLOOD -Live Version-', 'PINK BLOOD'], ['Face My Fears (English Version) -Live Version-', 'Face My Fears'],
                ['Hotel Lobby -Live Version-', 'Hotel Lobby'], ['Beautiful World (Da Capo Version) -Live Version-', 'Beautiful World'], ['About Me -Live Version-', 'About Me'],
                ['Face My Fears (Japanese Version) -Live Version-', 'Face My Fears'],
            ]],

            // ===== Utada 名義（番号の付かない企画盤） =====
            ['title' => 'Exodus', 'date' => '2004-09-08', 'special' => true, 'text' => self::UTADA, 'tracks' => [
                'Opening', 'Devil Inside', "Exodus '04", 'The Workout', 'Easy Breezy', 'Tippy Toe', 'Hotel Lobby', 'Animato', $plain('Crossover Interlude'), 'Kremlin Dusk',
                'You Make Me Want To Be A Man', "Wonder 'Bout", 'Let Me Give You My Love', 'About Me',
            ]],
            ['title' => 'This Is The One', 'date' => '2009-03-14', 'special' => true, 'text' => self::UTADA, 'tracks' => [
                'On and On', 'Merry Christmas Mr. Lawrence - FYI', 'Apple and Cinnamon', 'Taking My Money Back', 'This One (Crying Like a Child)', 'Automatic Part II', 'Dirty Desire',
                "Poppin'", 'Come Back to Me', 'Me Muero', $plain('Come Back to Me (Seamus Haji & Paul Emanuel Radio Edit)'), $plain('Come Back to Me (Quentin Harris Radio Edit)'),
            ]],
            // 曲名は英語（Wikipedia の日本盤の表記はカタカナだが、ほかの作品・曲名と同じ英語にする）
            ['title' => 'Utada The Best', 'date' => '2010-11-24', 'best' => true, 'special' => true, 'text' => self::UTADA, 'tracks' => [
                'Come Back to Me', 'Easy Breezy', 'Merry Christmas Mr. Lawrence - FYI', 'You Make Me Want To Be A Man', 'This One (Crying Like a Child)', "Exodus '04", 'Apple and Cinnamon',
                'Automatic Part II', 'Devil Inside', 'Kremlin Dusk', ['Sanctuary (Opening)', 'Passion'], ['Sanctuary (Ending)', 'Passion'],
                $plain("Exodus '04 (JJ FLORES Double J Radio Mix)"), $plain('Devil Inside (RJD2 Remix)'), $plain('Come Back to Me (Tony Moran & Warren Rigg Radio Edit)'),
                $plain('Dirty Desire (Mike Rizzo Radio Edit)'),
            ]],
        ];
    }
}
