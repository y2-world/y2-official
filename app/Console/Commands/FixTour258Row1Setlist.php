<?php

namespace App\Console\Commands;

use App\Models\DbSetlist;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixTour258Row1Setlist extends Command
{
    protected $signature = 'fix:tour258-row1-setlist {--dry-run : Show what would change without saving}';

    protected $description = 'Remove song 6 (拍手喝采), shift songs 7-8 to 6-7, insert 想望 (id 942) as new song 8, and remove alternative_title from song 13 (虹), for all row 1 patterns of tour 258';

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $setlists = DbSetlist::where('tour_id', 258)->where('row', 1)->orderBy('order_no')->get();

        foreach ($setlists as $s) {
            $items = $s->setlist ?? [];

            if (count($items) < 8) {
                $this->error("setlist_id={$s->id}: setlist has fewer than 8 songs, skipping");
                continue;
            }

            // 0-indexed: song 6 = index 5, song 7 = index 6, song 8 = index 7
            if (($items[5]['song'] ?? null) != 951) {
                $this->error("setlist_id={$s->id}: song 6 is not id 951 (拍手喝采) as expected, skipping");
                continue;
            }

            $newSong = [
                '_uuid' => (string) Str::uuid(),
                'song' => 942, // 想望
            ];

            $newItems = $items;
            // index 5,6,7 (songs 6,7,8) を、song7・song8・新規想望 の3件に置き換える
            // (song6=拍手喝采は削除、song7とsong8はそのまま前にずれる)
            array_splice($newItems, 5, 3, [$items[6], $items[7], $newSong]);

            // song 13 (虹, id 857) の alternative_title を削除
            foreach ($newItems as &$item) {
                if (($item['song'] ?? null) == 857) {
                    unset($item['alternative_title']);
                }
            }
            unset($item);

            $this->line("=== setlist_id={$s->id} order_no={$s->order_no} subtitle={$s->subtitle} ===");
            $this->line('  before 6-9: ' . collect(array_slice($items, 5, 4))->pluck('song')->implode(', '));
            $this->line('  after  6-9: ' . collect(array_slice($newItems, 5, 4))->pluck('song')->implode(', '));

            if (!$dryRun) {
                $s->setlist = $newItems;
                $s->save();
            }
        }

        $this->info($dryRun ? 'Dry run complete, no changes saved.' : 'All row 1 patterns updated.');
    }
}
