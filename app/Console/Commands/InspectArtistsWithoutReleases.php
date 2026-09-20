<?php

namespace App\Console\Commands;

use App\Models\Artist;
use Illuminate\Console\Command;

class InspectArtistsWithoutReleases extends Command
{
    protected $signature = 'inspect:artists-without-releases';

    protected $description = 'Debug: list artists with no albums and no singles registered';

    public function handle(): void
    {
        $artists = Artist::whereDoesntHave('albums')
            ->whereDoesntHave('singles')
            ->withCount('songs')
            ->get(['id', 'name']);

        foreach ($artists as $a) {
            $this->line("id={$a->id} name={$a->name} songs_count={$a->songs_count}");
        }
        $this->info('count=' . $artists->count());
    }
}
