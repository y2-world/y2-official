<?php

namespace App\Console\Commands;

use App\Models\DbSetlist;
use Illuminate\Console\Command;

class LinkUnlinkedFukuyamaSongs extends Command
{
    protected $signature = 'discography:link-unlinked-fukuyama-songs {--dry-run}';

    protected $description = 'Replace string song references in DbSetlist setlist/encore with the linked DbSong id for the 8 recently-added Fukuyama songs';

    // 曲名文字列（完全一致） => DbSong id
    private const TITLE_TO_SONG_ID = [
        'Soup' => 1894,
        'Dogons' => 1895,
        '無礼者たちへ' => 1896,
        'ププッとフムッとかいけつダンス 〜シリアーティ・ロック バージョン〜' => 1897,
        '炎のファイター 〜Carry on the fighting spirit〜' => 1898,
        '煌' => 1899,
        '恋の中' => 1900,
        '好きよ 好きよ 好きよ' => 1901,
    ];

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = 0;

        DbSetlist::get(['id', 'setlist', 'encore'])->each(function (DbSetlist $s) use ($dryRun, &$count) {
            $changed = false;

            foreach (['setlist', 'encore'] as $field) {
                $items = $s->$field ?? [];
                $fieldChanged = false;

                foreach ($items as $i => &$item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $song = $item['song'] ?? null;
                    if ($song === null || is_numeric($song)) {
                        continue;
                    }

                    $songId = self::TITLE_TO_SONG_ID[$song] ?? null;
                    if ($songId === null) {
                        continue;
                    }

                    $this->line("DbSetlist {$s->id} [{$field}][{$i}]: \"{$song}\" -> {$songId}");
                    $item['song'] = $songId;
                    $fieldChanged = true;
                    $count++;
                }
                unset($item);

                if ($fieldChanged) {
                    $s->$field = $items;
                    $changed = true;
                }
            }

            if ($changed && !$dryRun) {
                $s->save();
            }
        });

        $this->info(($dryRun ? 'Dry run complete. ' : 'Done. ') . "Total links: {$count}");
    }
}
