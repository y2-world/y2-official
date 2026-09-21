<?php

namespace App\Console\Commands;

use App\Models\SlSong;
use Illuminate\Console\Command;

class FindSlSongsByTitle extends Command
{
    protected $signature = 'discography:find-sl-songs {titles?*} {--unlinked : List all SlSong with no db_song_id} {--artist=}';

    protected $description = 'Look up SlSong records by title, or list all unlinked SlSong for an artist';

    public function handle(): void
    {
        $titles = $this->argument('titles');
        $unlinked = (bool) $this->option('unlinked');
        $artistFilter = $this->option('artist');

        if ($unlinked) {
            $query = SlSong::with('artist')->whereNull('db_song_id');
            if ($artistFilter) {
                $query->whereHas('artist', fn($q) => $q->where('name', $artistFilter));
            }
            $songs = $query->orderBy('title')->get();
            foreach ($songs as $s) {
                $artist = optional($s->artist)->name ?? '?';
                $this->line("sl_song_id={$s->id} title={$s->title} artist={$artist}");
            }
            $this->info('Total unlinked: ' . $songs->count());
            return;
        }

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
