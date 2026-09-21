<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

class FindUnlinkedSongs extends Command
{
    protected $signature = 'discography:find-unlinked {artistId}';

    protected $description = 'List DbSong records for an artist that are not referenced (by id) in any DbAlbum/DbSingle tracklist';

    public function handle(): void
    {
        $artistId = (int) $this->argument('artistId');

        $songs = DbSong::where('artist_id', $artistId)->get(['id', 'title']);

        $usedIds = [];
        foreach (DbAlbum::where('artist_id', $artistId)->get(['tracklist']) as $album) {
            foreach ($album->tracklist ?? [] as $track) {
                if (isset($track['id'])) {
                    $usedIds[$track['id']] = true;
                }
            }
        }
        foreach (DbSingle::where('artist_id', $artistId)->get(['tracklist']) as $single) {
            foreach ($single->tracklist ?? [] as $track) {
                if (isset($track['id'])) {
                    $usedIds[$track['id']] = true;
                }
            }
        }

        $unused = $songs->filter(fn($s) => !isset($usedIds[$s->id]));

        $this->info("total={$songs->count()} unused={$unused->count()}");
        foreach ($unused as $s) {
            $this->line("id={$s->id}: {$s->title}");
        }
    }
}
