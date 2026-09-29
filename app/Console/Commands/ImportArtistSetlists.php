<?php

namespace App\Console\Commands;

use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Support\SongTitleNormalizer;
use Illuminate\Console\Command;

class ImportArtistSetlists extends Command
{
    protected $signature = 'import:setlists {artist : Data file name in database/data/setlists (e.g. fukuyama)} {--dry-run : Show what would change without saving}';

    protected $description = 'Import one setlist per tour from database/data/setlists/{artist}.json into tours that have no setlist yet';

    private array $songTitlesById = [];
    private array $songIdsByTitle = [];
    private array $songIdsByCompactTitle = [];
    private array $unmatched = [];

    public function handle(): int
    {
        $path = database_path('data/setlists/' . basename($this->argument('artist')) . '.json');
        if (!is_file($path)) {
            $this->error("Data file not found: {$path}");
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $artistId = (int) $data['artist_id'];
        $dryRun = (bool) $this->option('dry-run');
        $this->createNewSongs($data['new_songs'] ?? [], $artistId, $dryRun);
        $this->indexSongs($artistId);
        $created = $skipped = 0;

        foreach ($data['setlists'] as $entry) {
            // ツアーはIDではなく開始日で特定する（新規登録したツアーはローカルと本番でIDが異なるため）
            $tour = DbConcert::where('artist_id', $artistId)->whereDate('date1', $entry['date1'])->first();
            if (!$tour) {
                $this->warn("tour not found: {$entry['date1']} {$entry['tour_title']}");
                $skipped++;
                continue;
            }
            $existing = DbSetlist::where('tour_id', $tour->id)->get();
            $mode = $entry['mode'] ?? 'new';
            // new: セットリスト未登録のツアーにだけ入れる / append: 既存パターンに無い公演の分だけ足す。
            // 追加しようとする段・順番が既に埋まっていれば、既に取り込み済みとみなして何もしない（再実行しても重複しない）
            $taken = $existing->map(fn ($s) => $s->row . ':' . $s->order_no)->all();
            $alreadyImported = collect($entry['patterns'])->contains(fn ($p) => in_array(($p['row'] ?? 1) . ':' . $p['order_no'], $taken, true));
            if (($mode === 'new' && $existing->isNotEmpty()) || ($mode === 'append' && ($existing->isEmpty() || $alreadyImported))) {
                $this->line("skip ({$mode}, " . ($existing->isEmpty() ? 'no setlist' : 'already has setlists') . "): {$entry['date1']} {$tour->title}");
                $skipped++;
                continue;
            }

            $this->info("{$mode}: {$entry['date1']} {$tour->title} (" . count($entry['patterns']) . ' patterns, ' . count($entry['subtitle_updates'] ?? []) . ' subtitle updates)');
            $created++;

            foreach ($entry['patterns'] as $pattern) {
                $setlist = array_map(fn ($item) => $this->toItem($item, $tour), $pattern['setlist']);
                $encore = array_map(fn ($item) => $this->toItem($item, $tour), $pattern['encore'] ?? []);

                if (!$dryRun) {
                    DbSetlist::create([
                        'tour_id' => $tour->id,
                        'order_no' => $pattern['order_no'],
                        'row' => $pattern['row'] ?? 1,
                        'subtitle' => $pattern['subtitle'] ?? null,
                        'setlist' => $setlist,
                        'encore' => $encore,
                    ]);
                }
            }

            foreach ($entry['subtitle_updates'] ?? [] as $update) {
                $target = $existing->first(fn ($s) => (int) $s->row === (int) $update['row'] && (int) $s->order_no === (int) $update['order_no']);
                if (!$target || $target->subtitle !== $update['expect']) {
                    $this->line("  subtitle update skipped (changed): row={$update['row']} order_no={$update['order_no']}");
                    continue;
                }
                $this->line("  subtitle: {$update['set']}");
                if (!$dryRun) {
                    $target->update(['subtitle' => $update['set']]);
                }
            }
        }

        foreach ($this->unmatched as $title => $tours) {
            $this->line("unmatched song (stored as text): {$title}  [" . implode(', ', array_unique($tours)) . ']');
        }
        $this->info(($dryRun ? '[dry-run] ' : '') . "created={$created} skipped={$skipped} unmatched_titles=" . count($this->unmatched));

        return self::SUCCESS;
    }

    // ライブでしか演奏されていない曲をDbSongに追加する。並び順は初演の時期に合わせ、
    // insert_before の曲の位置に入れて、それ以降の曲を1つずつ後ろにずらす
    private function createNewSongs(array $newSongs, int $artistId, bool $dryRun): void
    {
        foreach ($newSongs as $newSong) {
            if (DbSong::where('artist_id', $artistId)->where('title', $newSong['title'])->exists()) {
                continue;
            }
            $before = DbSong::where('artist_id', $artistId)->where('title', $newSong['insert_before'])->first();
            if (!$before) {
                $this->warn("new song skipped (insert_before not found): {$newSong['title']} before {$newSong['insert_before']}");
                continue;
            }
            $this->info("new song: {$newSong['title']} (sort_order {$before->sort_order}, before {$before->title})");
            if ($dryRun) {
                continue;
            }
            DbSong::where('artist_id', $artistId)->where('sort_order', '>=', $before->sort_order)->increment('sort_order');
            DbSong::create(['artist_id' => $artistId, 'title' => $newSong['title'], 'sort_order' => $before->sort_order]);
        }
    }

    private function indexSongs(int $artistId): void
    {
        foreach (DbSong::where('artist_id', $artistId)->get(['id', 'title']) as $song) {
            $this->songTitlesById[$song->id] = $song->title;
            $this->songIdsByTitle[SongTitleNormalizer::normalize($song->title)][] = $song->id;
            $this->songIdsByCompactTitle[$this->compact($song->title)][] = $song->id;
        }
    }

    private function compact(string $title): string
    {
        return preg_replace('/\s+/u', '', SongTitleNormalizer::normalize($title));
    }

    // 同名曲が複数ある等で1曲に決まらない場合は、誤リンクを避けて曲名の文字列のまま登録する
    private function resolveSongId(string $title): ?int
    {
        foreach ([$this->songIdsByTitle[SongTitleNormalizer::normalize($title)] ?? [], $this->songIdsByCompactTitle[$this->compact($title)] ?? []] as $ids) {
            $ids = array_values(array_unique($ids));
            if (count($ids) === 1) {
                return $ids[0];
            }
        }

        return null;
    }

    private function toItem(array $item, DbConcert $tour): array
    {
        // データ作成時に紐付けた曲ID。同じアーティストで曲名も一致するときだけ信用し、違えば曲名から探し直す
        $songId = null;
        if (!empty($item['song_id']) && isset($this->songTitlesById[(int) $item['song_id']])
            && $this->songTitlesById[(int) $item['song_id']] === ($item['song_title'] ?? null)) {
            $songId = (int) $item['song_id'];
        }
        $songId ??= $this->resolveSongId($item['title']);
        if ($songId === null) {
            $this->unmatched[$item['title']][] = $tour->date1 ? substr($tour->date1, 0, 10) : (string) $tour->id;
        }

        return [
            'song' => $songId !== null ? (string) $songId : $item['title'],
            'is_daily' => false,
            'medley' => (bool) ($item['medley'] ?? false),
            'daily_note' => null,
            'featuring' => $item['featuring'] ?? null,
            'featuring_type' => !empty($item['featuring']) ? ($item['featuring_type'] ?? 'guest') : null,
            'alternative_title' => $item['alternative_title'] ?? null,
        ];
    }
}
