<?php

namespace App\Console\Commands;

use App\Models\SlSetlist;
use Illuminate\Console\Command;

class FindAlternativeTitles extends Command
{
    protected $signature = 'discography:find-alt-titles {titles*}';

    protected $description = 'Search all SlSetlist setlist/encore items for the given alternative_title values';

    public function handle(): void
    {
        $targets = $this->argument('titles');

        SlSetlist::with('tour')->get(['id', 'tour_id', 'setlist', 'encore'])->each(function ($s) use ($targets) {
            foreach (['setlist', 'encore'] as $field) {
                foreach ((array) ($s->$field ?? []) as $item) {
                    if (!isset($item['alternative_title'])) {
                        continue;
                    }
                    if (!in_array($item['alternative_title'], $targets, true)) {
                        continue;
                    }
                    $artist = optional(optional($s->tour)->artist)->name;
                    $songRef = $item['song'] ?? '?';
                    $this->line("setlist_id={$s->id} field={$field} artist={$artist} alt={$item['alternative_title']} song_ref={$songRef}");
                }
            }
        });
    }
}
