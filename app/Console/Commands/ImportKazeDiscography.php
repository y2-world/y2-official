<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

// 藤井風の配信限定シングル・配信限定EPと、カバーアルバム・ライブアルバム・ベストアルバムを取り込む。
// オリジナルアルバム（HELP EVER HURT NEVER / LOVE ALL SERVE ALL / Prema）は登録済みなので触らない。
// 出典は ja.wikipedia.org「藤井風」ディスコグラフィ節、各アルバムの記事（2026-10-10）。
// - シングルはすべて配信なので、CDシングルの通し番号（single_id）は振らない。同じ日に2枚ある（へでもねーよ・青春病）ので title もキーにする
// - カバー曲は DbSong に登録しない（リンクなしの曲名だけ）。曲を [曲名, null] で表す
// - リミックス・Instrumental・Acapella は実演奏曲ではないのでリンクなし。Ballad・demo・Live は元の曲につなぎ、曲名を exception に付ける
class ImportKazeDiscography extends Command
{
    protected $signature = 'import:kaze-discography {--dry-run : Show matching without saving}';

    protected $description = 'Import Fujii Kaze digital singles, EPs and cover/live/best albums, matching tracks to existing DbSong records';

    private const ARTIST_ID = 38;

    // リンクしないトラック（リミックス・音源違い）。曲名だけ出す
    private const NON_SONG_PATTERN = '/\((?:[^)]*Remix|Instrumental|Acapella)\)|\[Acapella\]/u';

    private function normalize(string $title): string
    {
        $title = mb_convert_kana($title, 'as');
        $title = str_replace(['’', '‘'], "'", $title);
        return mb_strtolower(preg_replace('/\s+/u', '', $title), 'UTF-8');
    }

    /**
     * トラック1つ → tracklist の要素。$track は '曲名'（DbSong の曲名と同じなら、そのままリンク）か、
     * [表示する曲名, つなぐ DbSong の曲名 | null（カバーなどリンクしない）]
     */
    private function trackItem($track, array $idByTitle, array &$unmatched, string $release): ?array
    {
        [$label, $songTitle] = is_array($track) ? $track : [$track, $track];
        if ($songTitle === null || preg_match(self::NON_SONG_PATTERN, $label)) {
            return ['exception' => $label];
        }
        $id = $idByTitle[$this->normalize($songTitle)] ?? null;
        if ($id === null) {
            $unmatched[] = "{$release} / {$label}";
            return null;
        }
        $item = ['id' => (string) $id];
        if ($label !== $songTitle) {
            $item['exception'] = $label;
        }
        return $item;
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
            $tracks = array_values(array_filter(array_map(fn ($t) => $this->trackItem($t, $idByTitle, $unmatched, $s['title']), $s['tracks'])));
            $this->line(($s['ep'] ?? false ? '=== [EP] ' : '=== [single] ') . "{$s['title']} ({$s['date']}) === " . count($tracks) . '/' . count($s['tracks']));
            if (!$dryRun) {
                DbSingle::updateOrCreate(
                    ['artist_id' => self::ARTIST_ID, 'date' => $s['date'], 'title' => $s['title']],
                    ['single_id' => null, 'download' => true, 'ep' => $s['ep'] ?? false, 'tracklist' => $tracks]
                );
            }
        }

        foreach ($this->albumsData() as $a) {
            $tracks = array_values(array_filter(array_map(fn ($t) => $this->trackItem($t, $idByTitle, $unmatched, $a['title']), $a['tracks'])));
            $this->line("=== [album] {$a['title']} ({$a['date']}) === " . count($tracks) . '/' . count($a['tracks']));
            if (!$dryRun) {
                DbAlbum::updateOrCreate(
                    ['artist_id' => self::ARTIST_ID, 'date' => $a['date'], 'title' => $a['title']],
                    ['album_id' => null, 'best' => $a['best'] ?? false, 'mini' => false, 'tracklist' => $tracks]
                );
            }
        }

        foreach ($unmatched as $u) {
            $this->warn("unmatched: {$u}");
        }
        $this->info($dryRun ? 'Dry run complete.' : 'Import complete.');
    }

    private function singlesData(): array
    {
        $single = fn ($title, $date) => ['title' => $title, 'date' => $date, 'tracks' => [$title]];
        $cover = fn ($title) => [$title, null];

        return [
            // 配信限定シングル（先行配信を含む）
            $single('何なんw', '2019-11-18'),
            $single('もうええわ', '2019-12-24'),
            $single('優しさ', '2020-04-14'),
            $single('キリがないから', '2020-05-15'),
            $single('へでもねーよ', '2020-10-30'),
            $single('青春病', '2020-10-30'),
            $single('旅路', '2021-03-01'),
            $single('きらり', '2021-05-03'),
            $single('燃えよ', '2021-09-04'),
            $single('まつり', '2022-03-20'),
            $single('damn', '2022-09-30'),
            $single('grace', '2022-10-10'),
            $single('Workin’ Hard', '2023-08-25'),
            $single('花', '2023-10-13'),
            $single('満ちてゆく', '2024-03-15'),
            $single('Feelin’ Go(o)d', '2024-07-26'),
            $single('真っ白', '2025-02-28'),
            $single('Hachikō', '2025-06-13'),
            $single('Love Like This', '2025-08-01'),
            // 配信限定EP
            ['title' => '何なんw EP', 'date' => '2020-01-24', 'ep' => true, 'tracks' => ['何なんw', $cover('Close To You'), $cover("Don't Let Me Be Misunderstood"), $cover('Shake It Off')]],
            ['title' => 'もうええわ EP', 'date' => '2020-02-21', 'ep' => true, 'tracks' => ['もうええわ', $cover('Shape of You'), $cover('Back Stabbers'), $cover('Be Alright')]],
            ['title' => 'へでもねーよ EP', 'date' => '2020-12-04', 'ep' => true, 'tracks' => ['へでもねーよ', $cover('Just the Two of Us'), $cover('Sunny'), $cover('Sorry')]],
            ['title' => '青春病 EP', 'date' => '2020-12-11', 'ep' => true, 'tracks' => ['青春病', $cover('Teenage Dream'), $cover('Good As Hell'), $cover('Circles')]],
            ['title' => 'Kirari Remixes (Asia Edition)', 'date' => '2022-01-14', 'ep' => true, 'tracks' => [
                'Kirari (Original Remix)', 'Kirari (Daul Remix)', 'Kirari (FunkyMo Remix)', 'Kirari (pxzvc Remix)', 'Kirari (Kaze & Yaffle Just For Fun Remix)',
                'Kirari (Knopha Remix)', 'Kirari (Naken Remix)', 'Kirari (Yaffle Remix)', 'きらり',
            ]],
            ['title' => '花 EP', 'date' => '2023-11-03', 'ep' => true, 'tracks' => ['花', '花 (Instrumental)', ['花 (Ballad)', '花'], ['花 (demo)', '花']]],
            ['title' => '真っ白 EP', 'date' => '2025-03-14', 'ep' => true, 'tracks' => ['真っ白', '真っ白 (Acapella)', '真っ白 (Instrumental)', '真っ白 (KOBY SHY Remix)']],
            ['title' => 'Live at Apple Music Radio', 'date' => '2026-06-23', 'ep' => true, 'tracks' => [['You (Live at Apple Music Radio)', 'You'], ['Okay, Goodbye (Live at Apple Music Radio)', 'Okay, Goodbye']]],
            ['title' => 'You (feat. UMI)', 'date' => '2026-09-25', 'ep' => true, 'tracks' => [['You (feat. UMI)', 'You'], 'You (feat. UMI) [Acapella]', 'You (Instrumental)', 'You']],
        ];
    }

    private function albumsData(): array
    {
        $covers = fn (array $titles) => array_map(fn ($t) => [$t, null], $titles);

        return [
            ['title' => 'HELP EVER HURT COVER', 'date' => '2021-05-20', 'tracks' => $covers([
                'Close To You', 'Shape of You', 'Back Stabbers', 'Alfie', 'Be Alright', 'Beat It', "Don't Let Me Be Misunderstood",
                'My Eyes Adored You', 'Shake It Off', 'Stronger Than Me', 'Time After Time',
            ])],
            ['title' => 'LOVE ALL COVER ALL', 'date' => '2023-02-17', 'tracks' => $covers([
                'Sunny', 'No Tears Left To Cry', 'Hot Stuff', 'Sorry', 'Good As Hell', 'Just the Two of Us', 'Weak', 'Overprotected',
                'Teenage Dream', 'Eh, Eh', 'Circles',
            ])],
            ['title' => 'Best of Fujii Kaze 2020-2024', 'date' => '2024-05-28', 'best' => true, 'tracks' => [
                'まつり', 'Workin’ Hard', '何なんw', 'きらり', '花', 'ガーデン', 'damn', '死ぬのがいいわ', '旅路', '満ちてゆく',
            ]],
            // ライブアルバム。summer grace は音源になっていない曲なので DbSong に無い（リンクなし）
            ['title' => 'Fujii Kaze Stadium Live "Feelin\' Good"', 'date' => '2024-12-25', 'tracks' => [
                ['summer grace', null], 'Feelin’ Go(o)d', '花', 'ガーデン', '特にない', 'きらり', 'キリがないから', '燃えよ', '死ぬのがいいわ',
                'Workin’ Hard', 'damn', '旅路', '満ちてゆく', '青春病', '何なんw', 'まつり',
            ]],
        ];
    }
}
