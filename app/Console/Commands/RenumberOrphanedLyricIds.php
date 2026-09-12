<?php

namespace App\Console\Commands;

use App\Models\OfficialLyric;
use App\Models\OfficialRelease;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RenumberOrphanedLyricIds extends Command
{
    protected $signature = 'data:renumber-lyric-ids';
    protected $description = 'One-off fix: official_lyrics had ids 86-182 skipped (past bulk delete/reset). Renumber the two most recent records (183, 184) down to 86, 87 to close the gap, updating official_releases.tracklist references and resetting the auto-increment counter.';

    public function handle()
    {
        $map = [183 => 86, 184 => 87];

        DB::beginTransaction();

        try {
            foreach ($map as $oldId => $newId) {
                $lyric = OfficialLyric::find($oldId);
                if (!$lyric) {
                    throw new Exception("Lyric id={$oldId} not found");
                }
                DB::table('official_lyrics')->where('id', $oldId)->update(['id' => $newId]);
                $this->info("Renamed lyric id {$oldId} -> {$newId} (title: {$lyric->title})");
            }

            foreach ([23, 29] as $releaseId) {
                $release = OfficialRelease::find($releaseId);
                $tracklist = $release->tracklist;
                $changed = false;
                foreach ($tracklist as &$track) {
                    if (isset($track['id']) && isset($map[(int) $track['id']])) {
                        $old = $track['id'];
                        $track['id'] = (string) $map[(int) $track['id']];
                        $this->info("Release {$releaseId} tracklist: {$old} -> {$track['id']}");
                        $changed = true;
                    }
                }
                unset($track);
                if ($changed) {
                    $release->tracklist = $tracklist;
                    $release->save();
                }
            }

            DB::statement('ALTER TABLE official_lyrics AUTO_INCREMENT = 88');
            $this->info('AUTO_INCREMENT reset to 88');

            DB::commit();
            $this->info('SUCCESS');
        } catch (Exception $e) {
            DB::rollBack();
            $this->error('FAILED: ' . $e->getMessage());
        }
    }
}
