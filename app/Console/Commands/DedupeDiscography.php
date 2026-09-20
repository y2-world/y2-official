<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use Illuminate\Console\Command;

class DedupeDiscography extends Command
{
    protected $signature = 'discography:dedupe {artistId} {--type=both : album, single, or both} {--dry-run}';

    protected $description = 'Find/remove duplicate DbAlbum or DbSingle records for an artist (e.g. caused by title text changes before a re-import)';

    public function handle(): void
    {
        $artistId = (int) $this->argument('artistId');
        $dryRun = (bool) $this->option('dry-run');
        $type = $this->option('type');

        if (in_array($type, ['album', 'both'])) {
            $this->dedupe(DbAlbum::class, $artistId, $dryRun, 'title', 'date');
        }
        if (in_array($type, ['single', 'both'])) {
            $this->dedupe(DbSingle::class, $artistId, $dryRun, 'title', 'date');
        }
    }

    private function dedupe(string $model, int $artistId, bool $dryRun, string ...$groupBy): void
    {
        $records = $model::where('artist_id', $artistId)->orderBy('id')->get();
        $this->line("=== {$model} (artist_id={$artistId}) total=" . $records->count() . ' ===');

        // dateだけでグルーピングし、同じ日付に複数タイトルがある場合を重複候補として表示
        $byDate = $records->groupBy('date');
        foreach ($byDate as $date => $group) {
            if ($group->count() <= 1) {
                continue;
            }
            $this->warn("date={$date} has " . $group->count() . ' records:');
            foreach ($group as $r) {
                $this->warn("  id={$r->id} title=\"{$r->title}\"");
            }
        }

        if ($dryRun) {
            return;
        }
    }
}
