<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSetlist;
use App\Models\DbSingle;
use App\Models\DbSong;
use App\Models\SlSong;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShiftDbSongIds extends Command
{
    protected $signature = 'data:shift-db-song-ids {from : Shift every DbSong id >= this value up by one} {--dry-run : Report what would change without writing}';

    protected $description = 'One-off: shift db_songs.id (and every place that references it) up by one for every id >= {from}, freeing up {from} for a song to be inserted there. Must run id shifts from the highest id down to avoid unique constraint collisions.';

    public function handle(): int
    {
        $from = (int) $this->argument('from');
        $dryRun = (bool) $this->option('dry-run');

        $ids = DbSong::where('id', '>=', $from)->orderByDesc('id')->pluck('id');

        if ($ids->isEmpty()) {
            $this->info("No DbSong with id >= {$from}. Nothing to do.");
            return self::SUCCESS;
        }

        $this->info('Will shift ' . $ids->count() . " DbSong ids up by one, from highest ({$ids->first()}) down to {$from}.");

        if ($dryRun) {
            $this->info('Dry run only, no changes made.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $oldId) {
                $newId = $oldId + 1;

                // 1. db_songs.id 自体を先に更新する。sl_songs.db_song_idの外部キー制約は
                // 「参照先が実在するid」であることを要求するため、参照元より先に参照先
                // （db_songs.id）を新しいidへ動かしておく必要がある。
                DbSong::where('id', $oldId)->update(['id' => $newId]);

                // 2. sl_songs.db_song_id（外部キー制約あり）
                SlSong::where('db_song_id', $oldId)->update(['db_song_id' => $newId]);

                // 3. db_albums.tracklist / db_singles.tracklist 内のJSON 'id'（文字列として保持）
                $this->shiftTracklistReferences(DbAlbum::class, $oldId, $newId);
                $this->shiftTracklistReferences(DbSingle::class, $oldId, $newId);

                // 4. db_setlists.setlist / encore 内のJSON 'song'（数値または文字列として保持）
                $this->shiftSetlistReferences($oldId, $newId);

                $this->line("  {$oldId} -> {$newId}");
            }
        });

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function shiftTracklistReferences(string $modelClass, int $oldId, int $newId): void
    {
        $oldIdStr = (string) $oldId;
        $newIdStr = (string) $newId;

        $records = $modelClass::whereNotNull('tracklist')->get();
        foreach ($records as $record) {
            $tracklist = $record->tracklist;
            if (!is_array($tracklist)) {
                continue;
            }

            $changed = false;
            foreach ($tracklist as &$track) {
                if (isset($track['id']) && (string) $track['id'] === $oldIdStr) {
                    $track['id'] = $newIdStr;
                    $changed = true;
                }
            }
            unset($track);

            if ($changed) {
                $record->tracklist = $tracklist;
                $record->save();
            }
        }
    }

    private function shiftSetlistReferences(int $oldId, int $newId): void
    {
        $setlists = DbSetlist::all();
        foreach ($setlists as $setlist) {
            $changed = false;

            foreach (['setlist', 'encore'] as $field) {
                $items = $setlist->{$field};
                if (!is_array($items)) {
                    continue;
                }

                foreach ($items as &$item) {
                    if (isset($item['song']) && is_numeric($item['song']) && (int) $item['song'] === $oldId) {
                        $item['song'] = $newId;
                        $changed = true;
                    }
                }
                unset($item);

                if ($changed) {
                    $setlist->{$field} = $items;
                }
            }

            if ($changed) {
                $setlist->save();
            }
        }
    }
}
