<?php

namespace App\Console\Commands;

use App\Models\DbConcert;
use Illuminate\Console\Command;

class ImportArtistTours extends Command
{
    protected $signature = 'import:tours {artist : Data file name in database/data/tours (e.g. fukuyama)} {--dry-run : Show what would change without saving} {--update : Overwrite fields of tours that already exist}';

    protected $description = 'Import tours/lives missing from the database from database/data/tours/{artist}.json';

    private const FIELDS = ['title', 'type', 'date1', 'date2', 'venue', 'schedule'];

    public function handle(): int
    {
        $path = database_path('data/tours/' . basename($this->argument('artist')) . '.json');
        if (!is_file($path)) {
            $this->error("Data file not found: {$path}");
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $artistId = (int) $data['artist_id'];
        $dryRun = (bool) $this->option('dry-run');
        $update = (bool) $this->option('update');
        $created = $updated = $skipped = 0;

        foreach ($data['tours'] as $tour) {
            $tour = array_intersect_key($tour, array_flip(self::FIELDS));

            // 開始日で同じツアーかを判定する。既存があれば、--update 指定時以外は管理画面での編集を守るため触らない
            $existing = DbConcert::where('artist_id', $artistId)->whereDate('date1', $tour['date1'])->first();

            if ($existing && !$update) {
                $this->line("skip (exists id={$existing->id}): {$tour['date1']} {$existing->title}");
                $skipped++;
                continue;
            }

            $this->info(($existing ? 'update' : 'create') . ": {$tour['date1']} {$tour['title']}");
            if ($dryRun) {
                $existing ? $updated++ : $created++;
                continue;
            }

            if ($existing) {
                $existing->update($tour);
                $updated++;
            } else {
                DbConcert::create($tour + ['artist_id' => $artistId]);
                $created++;
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "created={$created} updated={$updated} skipped={$skipped}");

        $this->applyFixes($data['fixes'] ?? [], $artistId, $dryRun);

        return self::SUCCESS;
    }

    // 既存ツアーの誤りの修正。expect の値が現在値と一致するときだけ set を当てるので、
    // 既に直っている行や管理画面で別の値に直された行は触らない。
    private function applyFixes(array $fixes, int $artistId, bool $dryRun): void
    {
        foreach ($fixes as $fix) {
            $concert = DbConcert::where('artist_id', $artistId)->find($fix['id']);
            if (!$concert) {
                $this->warn("fix skipped (not found): id={$fix['id']}");
                continue;
            }

            $current = collect($fix['expect'])->map(fn ($v, $field) => $this->comparable($concert, $field));
            if ($current->all() != $fix['expect']) {
                $this->line("fix skipped (already changed): id={$fix['id']} " . json_encode($current, JSON_UNESCAPED_UNICODE));
                continue;
            }

            $this->info("fix id={$fix['id']}: " . json_encode($fix['set'], JSON_UNESCAPED_UNICODE));
            if (!$dryRun) {
                $concert->update($fix['set']);
            }
        }
    }

    private function comparable(DbConcert $concert, string $field): ?string
    {
        $value = $concert->getRawOriginal($field);
        return in_array($field, ['date1', 'date2'], true) && $value !== null ? substr($value, 0, 10) : $value;
    }
}
