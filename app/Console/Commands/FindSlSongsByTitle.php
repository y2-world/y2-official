<?php

namespace App\Console\Commands;

use App\Models\SlSong;
use Illuminate\Console\Command;

class FindSlSongsByTitle extends Command
{
    protected $signature = 'discography:find-sl-songs {titles*}';

    protected $description = 'Look up SlSong records by title and show their db_song_id linkage';

    public function handle(): void
    {
        $titles = $this->argument('titles');

        foreach ($titles as $title) {
            $songs = SlSong::with('artist', 'dbSong')->where('title', $title)->get();
            if ($songs->isEmpty()) {
                $this->warn("not found: {$title}");
                continue;
            }
            foreach ($songs as $s) {
                $artist = optional($s->artist)->name ?? '?';
                $dbTitle = optional($s->dbSong)->title;
                $this->line("sl_song_id={$s->id} title={$s->title} artist={$artist} db_song_id={$s->db_song_id} db_song_title=" . ($dbTitle ?? 'null'));
            }
        }
    }
}
