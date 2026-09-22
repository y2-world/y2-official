<?php

namespace App\Console\Commands;

use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

class RegisterWorldNeedsLove extends Command
{
    protected $signature = 'discography:register-world-needs-love {--dry-run}';

    protected $description = 'Register "World needs love" (Earth Harmony feat. w-inds.) as a DbSong at its correct chronological position, and link it in the single tracklist';

    private const ARTIST_ID = 1; // w-inds.
    private const SINGLE_ID = 383;
    private const TITLE = 'World needs love';

    // シングルのM1がWorld needs love、M2が既存曲「I'll be there」(id=1220, sort_order=20)。
    // トラック順に合わせ、その直前（sort_order=20）に挿入し、以降を1つずつ繰り下げる。
    private const INSERT_AT_SORT_ORDER = 20;

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');

        $existing = DbSong::where('artist_id', self::ARTIST_ID)->where('title', self::TITLE)->first();
        if ($existing) {
            $this->error("DbSong already exists: id={$existing->id}");
            return;
        }

        $single = DbSingle::find(self::SINGLE_ID);
        if (!$single) {
            $this->error('Single not found: ' . self::SINGLE_ID);
            return;
        }

        $tracklist = $single->tracklist ?? [];
        $targetIndex = null;
        foreach ($tracklist as $i => $track) {
            if (($track['exception'] ?? null) === self::TITLE && !isset($track['id'])) {
                $targetIndex = $i;
                break;
            }
        }

        if ($targetIndex === null) {
            $this->error('Tracklist entry not found for: ' . self::TITLE);
            return;
        }

        $this->line('Will create DbSong: title="' . self::TITLE . '" artist_id=' . self::ARTIST_ID . ' at sort_order=' . self::INSERT_AT_SORT_ORDER);
        $this->line("Will shift sort_order >= " . self::INSERT_AT_SORT_ORDER . " by +1 for artist_id=" . self::ARTIST_ID);
        $this->line("Will link tracklist[{$targetIndex}] to the new song");

        if ($dryRun) {
            $this->info('Dry run complete.');
            return;
        }

        DbSong::where('artist_id', self::ARTIST_ID)
            ->where('sort_order', '>=', self::INSERT_AT_SORT_ORDER)
            ->orderByDesc('sort_order')
            ->each(function (DbSong $s) {
                $s->update(['sort_order' => $s->sort_order + 1]);
            });

        $song = DbSong::create([
            'title' => self::TITLE,
            'artist_id' => self::ARTIST_ID,
            'sort_order' => self::INSERT_AT_SORT_ORDER,
        ]);

        $tracklist[$targetIndex] = ['id' => $song->id];
        $single->tracklist = $tracklist;
        $single->save();

        $this->info("Done. DbSong id={$song->id}, linked in single {$single->id} tracklist[{$targetIndex}].");
    }
}
