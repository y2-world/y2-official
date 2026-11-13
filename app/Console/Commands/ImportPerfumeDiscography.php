<?php

namespace App\Console\Commands;

use App\Models\Artist;
use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

// Perfume（アーティストはもうある）の曲・シングル・アルバムを取り込む。曲（DbSong）はまだ無いので、発売順に作る。
// データは database/data/discography/perfume.json（storage/app/setlist-work/perfume/build_perfume.py が作ったもの）。
// - CDシングルには通し番号（single_id。広島限定・インディーズは番号なし）、配信シングル・EP には番号なし
// - オリジナルアルバムに番号、ベスト（best）、コンピレーション・ボックス・別の版（special）は番号なし
// - インスト・カラオケ・Remix・カバー・イントロは曲にしない（曲名だけ）。名義が Perfume でない作品は、説明（text）に名義を書く
// - 何度流しても同じになる（曲は曲名、作品は日付と題名で見つけて上書き）
class ImportPerfumeDiscography extends Command
{
    protected $signature = 'import:perfume-discography {--dry-run : Show what would be created without saving}';

    protected $description = 'Import Perfume songs, singles and albums from database/data/discography/perfume.json';

    private function normalize(string $title): string
    {
        $title = mb_convert_kana($title, 'as');
        $title = str_replace(['’', '‘', '“', '”'], ["'", "'", '"', '"'], $title);

        return mb_strtolower(preg_replace('/[\s・～〜~\-‐]+/u', '', $title), 'UTF-8');
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $data = json_decode(file_get_contents(database_path('data/discography/perfume.json')), true);

        $artist = Artist::where('name', $data['artist']['name'])->first();
        if (!$artist) {
            $this->error("artist not found: {$data['artist']['name']}");
            return self::FAILURE;
        }
        $artistId = $artist->id;

        // 曲：発売順に作る（もうある曲はそのまま使い、曲名とアーティスト表記を新しい書き方にする）
        $idByTitle = [];
        foreach (DbSong::where('artist_id', $artistId)->get(['id', 'title']) as $song) {
            $idByTitle[$this->normalize($song->title)] = $song->id;
        }
        $created = 0;
        foreach ($data['songs'] as $i => $s) {
            $key = $this->normalize($s['title']);
            $attributes = ['title' => $s['title'], 'credit' => $s['credit'], 'sort_order' => $i];
            if (isset($idByTitle[$key])) {
                if (!$dryRun) {
                    DbSong::whereKey($idByTitle[$key])->update($attributes);
                }
                continue;
            }
            $created++;
            $idByTitle[$key] = $dryRun ? 0 : DbSong::create(['artist_id' => $artistId] + $attributes)->id;
        }
        $this->info("songs: {$created} new, " . count($data['songs']) . ' in data');

        $counts = ['single' => 0, 'album' => 0];
        foreach ($data['releases'] as $r) {
            $tracks = [];
            foreach ($r['tracks'] as $t) {
                $item = [];
                if ($t['link'] === null) {
                    $item['exception'] = $t['label'];
                } else {
                    $item['id'] = (string) $idByTitle[$this->normalize($t['link'])];
                    if ($t['label'] !== $t['link']) {
                        $item['exception'] = $t['label'];
                    }
                }
                if (!empty($t['disc'])) {
                    $item['disc'] = $t['disc'];
                }
                $tracks[] = $item;
            }

            $isSingle = in_array($r['kind'], ['single', 'digital_single', 'ep'], true);
            $this->line(sprintf('%-14s %s %s (%d tracks)%s', $r['kind'], $r['date'], $r['title'], count($tracks), $r['credit'] ? " / {$r['credit']}" : ''));
            if ($dryRun) {
                continue;
            }
            $key = ['artist_id' => $artistId, 'date' => $r['date'], 'title' => $r['title']];
            $text = $r['credit'] ? "{$r['credit']} 名義" : null;
            if ($isSingle) {
                $saved = DbSingle::updateOrCreate($key, [
                    'single_id' => $r['kind'] === 'single' ? $r['num'] : null,
                    'download' => $r['kind'] !== 'single',
                    'ep' => $r['kind'] === 'ep',
                    'text' => $text,
                    'tracklist' => $tracks,
                ]);
                $counts['single']++;
            } else {
                $saved = DbAlbum::updateOrCreate($key, [
                    'album_id' => $r['kind'] === 'album' ? $r['num'] : null,
                    'best' => $r['kind'] === 'best' ? 1 : 0,
                    'mini' => 0,
                    // コンピレーション・ボックス・別の版は、番号の付かない企画盤
                    'special' => in_array($r['kind'], ['compilation', 'special'], true) ? 1 : 0,
                    'text' => $text,
                    'tracklist' => $tracks,
                ]);
                $counts['album']++;
            }
            // 決まり11：書き方を直して題名が変わったとき、同じ日の前の書き方の作品が残らないように消す
            $model = $isSingle ? DbSingle::class : DbAlbum::class;
            foreach ($model::where('artist_id', $artistId)->where('date', $r['date'])->whereKeyNot($saved->id)->get() as $old) {
                if ($this->normalize($old->title) === $this->normalize($r['title'])) {
                    $this->line("remove old: {$old->title}");
                    $old->delete();
                }
            }
        }
        $this->info("singles: {$counts['single']}, albums: {$counts['album']}");

        return self::SUCCESS;
    }
}
