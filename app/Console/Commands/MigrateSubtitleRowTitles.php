<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DbSetlist;
use App\Models\DbSetlistRow;

class MigrateSubtitleRowTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setlist:migrate-subtitle-row-titles {--dry-run : 変更内容を保存せずに確認だけする}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'db_setlists.subtitleの1行目に間借りしていた段タイトル（例:「アリーナ公演」）をdb_setlist_rowsへ移行する';

    // subtitleの中の日付らしいパターン（renderSubtitleWithGreyedVenuesと同じ判定基準）
    private const DATE_PATTERN = '/\d{1,2}\.\d{1,2}/u';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $migrated = 0;

        $setlists = DbSetlist::orderBy('tour_id')->orderBy('row')->orderBy('order_no')->get();

        // tour_id + row ごとに、その段の最初のパターン（order_noが最小のもの）だけを対象にする
        $firstByRow = $setlists->groupBy(fn ($s) => $s->tour_id . '-' . $s->row)
            ->map(fn ($group) => $group->sortBy('order_no')->first());

        foreach ($firstByRow as $setlist) {
            $lines = preg_split('/\r\n|\r|\n/', trim($setlist->subtitle ?? ''));
            $lines = array_values(array_filter($lines, fn ($line) => trim($line) !== ''));

            if (count($lines) < 2) {
                continue;
            }

            $firstLine = trim($lines[0]);

            // 1行目に日付パターンが含まれる場合は段タイトルではなく通常の日程行なので対象外
            if ($firstLine === '' || preg_match(self::DATE_PATTERN, $firstLine)) {
                continue;
            }

            $tour = $setlist->tour;
            $this->line("tour #{$setlist->tour_id} \"" . ($tour->title ?? '?') . "\" row={$setlist->row}: \"{$firstLine}\"");
            $migrated++;

            if ($dryRun) {
                continue;
            }

            DbSetlistRow::updateOrCreate(
                ['tour_id' => $setlist->tour_id, 'row' => $setlist->row],
                ['title' => $firstLine]
            );

            $remainingLines = array_slice($lines, 1);
            $setlist->subtitle = implode("\n", $remainingLines);
            $setlist->save();
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '') . "移行した段タイトル数: {$migrated}");

        return Command::SUCCESS;
    }
}
