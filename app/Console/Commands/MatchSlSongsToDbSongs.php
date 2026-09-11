<?php

namespace App\Console\Commands;

use App\Models\DbSong;
use App\Models\SlSong;
use App\Support\SongTitleNormalizer;
use Illuminate\Console\Command;

class MatchSlSongsToDbSongs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'songs:match-sl-to-db {--force : Re-match songs that already have a db_song_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link sl_songs to db_songs by matching normalized titles within the same artist';

    public function handle()
    {
        $query = SlSong::query();
        if (!$this->option('force')) {
            $query->whereNull('db_song_id');
        }

        $slSongs = $query->get();
        $this->info("Checking {$slSongs->count()} sl_songs rows...");

        $matched = 0;
        $ambiguous = 0;
        $unmatched = 0;

        // アーティストごとにDbSongをまとめて取得し、正規化タイトルでグルーピングしておく
        $dbSongsByArtist = DbSong::all()->groupBy('artist_id');

        foreach ($slSongs as $slSong) {
            if (!$slSong->artist_id) {
                $unmatched++;
                continue;
            }

            $candidates = $dbSongsByArtist->get($slSong->artist_id, collect());
            $normalizedTitle = SongTitleNormalizer::normalize($slSong->title);
            $matches = $candidates->filter(
                fn(DbSong $candidate) => SongTitleNormalizer::normalize($candidate->title) === $normalizedTitle
            );

            if ($matches->count() === 1) {
                $slSong->db_song_id = $matches->first()->id;
                $slSong->save();
                $matched++;
            } elseif ($matches->count() > 1) {
                $this->warn("Ambiguous match for SlSong #{$slSong->id} \"{$slSong->title}\" (artist_id={$slSong->artist_id}): {$matches->count()} candidates");
                $ambiguous++;
            } else {
                $unmatched++;
            }
        }

        $this->info("Matched: {$matched}, Ambiguous: {$ambiguous}, Unmatched: {$unmatched}");
        return 0;
    }
}
