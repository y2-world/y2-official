<?php

namespace App\Console\Commands;

use App\Models\Artist;
use App\Models\DbSong;
use Illuminate\Console\Command;

class InspectSongsForArtists extends Command
{
    protected $signature = 'inspect:songs-for-artists {artistIds*}';

    protected $description = 'Debug: list all DbSong titles for given artist ids';

    public function handle(): void
    {
        foreach ($this->argument('artistIds') as $artistId) {
            $artist = Artist::find($artistId);
            $this->line("=== artist_id={$artistId} name=" . ($artist->name ?? '?') . ' ===');
            $songs = DbSong::where('artist_id', $artistId)->orderBy('id')->get(['id', 'title']);
            foreach ($songs as $s) {
                $this->line("{$s->id}\t{$s->title}");
            }
            $this->line('count=' . $songs->count());
        }
    }
}
