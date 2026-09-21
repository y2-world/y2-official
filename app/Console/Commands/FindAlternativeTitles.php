<?php

namespace App\Console\Commands;

use App\Models\SlSetlist;
use Illuminate\Console\Command;

class FindAlternativeTitles extends Command
{
    protected $signature = 'discography:find-alt-titles {titles?*} {--all : List every alternative_title found, grouped by value}';

    protected $description = 'Search all SlSetlist setlist/encore items for alternative_title values';

    public function handle(): void
    {
        $targets = $this->argument('titles');
        $listAll = (bool) $this->option('all');

        $occurrences = [];

        SlSetlist::with('artist')->get(['id', 'artist_id', 'title', 'setlist', 'encore', 'fes_setlist', 'fes_encore'])
            ->each(function ($s) use ($targets, $listAll, &$occurrences) {
                foreach (['setlist', 'encore', 'fes_setlist', 'fes_encore'] as $field) {
                    foreach ((array) ($s->$field ?? []) as $item) {
                        if (!is_array($item) || !isset($item['alternative_title']) || $item['alternative_title'] === '') {
                            continue;
                        }
                        $alt = $item['alternative_title'];
                        if (!$listAll && !in_array($alt, $targets, true)) {
                            continue;
                        }
                        $artist = optional($s->artist)->name ?? '?';
                        $songRef = $item['song'] ?? '?';
                        $occurrences[] = "setlist_id={$s->id} title={$s->title} field={$field} artist={$artist} alt={$alt} song_ref={$songRef}";
                    }
                }
            });

        foreach ($occurrences as $line) {
            $this->line($line);
        }
        $this->info('Total occurrences: ' . count($occurrences));
    }
}
