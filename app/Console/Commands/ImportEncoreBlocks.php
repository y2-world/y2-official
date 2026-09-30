<?php

namespace App\Console\Commands;

use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\SlSetlist;
use App\Support\EncoreBlocks;
use Illuminate\Console\Command;

class ImportEncoreBlocks extends Command
{
    protected $signature = 'import:encore-blocks {artist : Data file name in database/data/encore_blocks (e.g. fukuyama)} {--dry-run : Show what would change without saving}';

    protected $description = 'Mark where the 2nd and later encores (DOUBLE ENCORE) start, from database/data/encore_blocks/{artist}.json';

    public function handle(): int
    {
        $path = database_path('data/encore_blocks/' . basename($this->argument('artist')) . '.json');
        if (!is_file($path)) {
            $this->error("Data file not found: {$path}");
            return self::FAILURE;
        }
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $dryRun = (bool) $this->option('dry-run');
        $changed = $unchanged = $missing = 0;

        foreach ($data['changes'] as $change) {
            // ローカルと本番でIDが違うことがあるので、公式はツアー開始日・段・順番、自分のセットリストは日付・タイトルで特定する
            if ($change['table'] === 'db_setlists') {
                $tourIds = DbConcert::where('artist_id', $data['artist_id'])->whereDate('date1', $change['tour_date1'])->pluck('id');
                $setlist = DbSetlist::whereIn('tour_id', $tourIds)->where('row', $change['row'])->where('order_no', $change['order_no'])->first();
                // 段・順番が並べ替えられている場合は、同じツアーの同じタイトル（subtitle）のパターンを使う
                if ((!$setlist || $setlist->subtitle !== ($change['subtitle'] ?? null)) && isset($change['subtitle'])) {
                    $setlist = DbSetlist::whereIn('tour_id', $tourIds)->where('subtitle', $change['subtitle'])->first() ?? $setlist;
                }
                $label = "{$change['tour_date1']} row={$change['row']} order_no={$change['order_no']}";
            } else {
                $setlist = SlSetlist::whereDate('date', $change['date'])->where('title', $change['title'])->first();
                $label = "{$change['date']} {$change['title']}";
            }
            $encore = $setlist ? array_values((array) ($setlist->encore ?? [])) : [];
            // データ作成時と曲数が変わっていたら（手で直された等）、区切りの位置がずれるので何もしない
            if (!$setlist || count($encore) !== $change['points'][0] + count($change['double_encore'])) {
                $this->warn("skip (not found or encore changed): {$label}");
                $missing++;
                continue;
            }

            $updated = array_map(function ($item, $i) use ($change) {
                unset($item[EncoreBlocks::START_FLAG]);
                if (in_array($i, $change['points'], true)) {
                    $item[EncoreBlocks::START_FLAG] = true;
                }
                return $item;
            }, $encore, array_keys($encore));
            if ($updated === $encore) {
                $unchanged++;
                continue;
            }
            $this->line("mark: {$label} → ENCORE 2 から " . implode(' / ', $change['double_encore']));
            if (!$dryRun) {
                $setlist->encore = $updated;
                $setlist->save();
            }
            $changed++;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "changed={$changed} unchanged={$unchanged} skipped={$missing}");

        return self::SUCCESS;
    }
}
