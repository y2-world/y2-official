<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setlist;
use App\Models\SetlistSong;
use App\Artist;

class UpdateSetlistsWithSongIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setlist:update-song-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update setlists JSON to replace song titles with setlist_songs IDs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting update of setlists with song IDs...');
        
        $setlists = Setlist::all();
        $totalSetlists = $setlists->count();
        $processed = 0;
        $updated = 0;
        $notFound = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($totalSetlists);
        $bar->start();

        foreach ($setlists as $setlist) {
            try {
                $hasChanges = false;
                $updatedSetlist = $setlist->setlist;
                $updatedEncore = $setlist->encore;
                $updatedFesSetlist = $setlist->fes_setlist;
                $updatedFesEncore = $setlist->fes_encore;

                // 単独ライブの setlist
                if (!empty($setlist->setlist) && is_array($setlist->setlist)) {
                    $updatedSetlist = $this->updateSongEntries($setlist->setlist, $setlist->artist_id);
                    if ($updatedSetlist !== $setlist->setlist) {
                        $hasChanges = true;
                    }
                }

                // 単独ライブの encore
                if (!empty($setlist->encore) && is_array($setlist->encore)) {
                    $updatedEncore = $this->updateSongEntries($setlist->encore, $setlist->artist_id);
                    if ($updatedEncore !== $setlist->encore) {
                        $hasChanges = true;
                    }
                }

                // フェスの fes_setlist
                if (!empty($setlist->fes_setlist) && is_array($setlist->fes_setlist)) {
                    $updatedFesSetlist = $this->updateFesSongEntries($setlist->fes_setlist);
                    if ($updatedFesSetlist !== $setlist->fes_setlist) {
                        $hasChanges = true;
                    }
                }

                // フェスの fes_encore
                if (!empty($setlist->fes_encore) && is_array($setlist->fes_encore)) {
                    $updatedFesEncore = $this->updateFesSongEntries($setlist->fes_encore);
                    if ($updatedFesEncore !== $setlist->fes_encore) {
                        $hasChanges = true;
                    }
                }

                if ($hasChanges) {
                    $setlist->setlist = $updatedSetlist;
                    $setlist->encore = $updatedEncore;
                    $setlist->fes_setlist = $updatedFesSetlist;
                    $setlist->fes_encore = $updatedFesEncore;
                    $setlist->save();
                    $updated++;
                }

                $processed++;
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nError processing setlist ID {$setlist->id}: " . $e->getMessage());
                $errors++;
                $processed++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Update completed!");
        $this->info("Processed: {$processed} setlists");
        $this->info("Updated: {$updated} setlists");
        $this->info("Songs not found: {$notFound}");
        $this->info("Errors: {$errors}");

        return Command::SUCCESS;
    }

    /**
     * Update song entries in regular setlist/encore
     *
     * @param array $entries
     * @param mixed $defaultArtistId
     * @return array
     */
    private function updateSongEntries($entries, $defaultArtistId)
    {
        $updated = [];
        foreach ($entries as $entry) {
            $updatedEntry = $entry;
            
            if (isset($entry['song']) && !is_numeric($entry['song'])) {
                // 文字列のタイトルをIDに変換
                $title = trim($entry['song']);
                $title = preg_replace('/\s*\[[^\]]+\]/u', '', $title);
                $title = trim($title);
                
                if (!empty($title)) {
                    $song = SetlistSong::where('title', $title)
                        ->where('artist_id', $defaultArtistId)
                        ->first();
                    
                    if ($song) {
                        $updatedEntry['song'] = $song->id;
                    }
                }
            }
            
            $updated[] = $updatedEntry;
        }
        
        return $updated;
    }

    /**
     * Update song entries in festival setlist/encore
     *
     * @param array $entries
     * @return array
     */
    private function updateFesSongEntries($entries)
    {
        $updated = [];
        foreach ($entries as $entry) {
            $updatedEntry = $entry;
            
            if (isset($entry['song']) && !is_numeric($entry['song'])) {
                // 文字列のタイトルをIDに変換
                $title = trim($entry['song']);
                $title = preg_replace('/\s*\[[^\]]+\]/u', '', $title);
                $title = trim($title);
                
                if (!empty($title)) {
                    $artistId = null;
                    
                    // JSON の artist フィールドから artist_id を抜き出す
                    if (isset($entry['artist']) && !empty($entry['artist'])) {
                        if (is_numeric($entry['artist'])) {
                            $artistId = (int) $entry['artist'];
                        } elseif (is_string($entry['artist']) && is_numeric(trim($entry['artist']))) {
                            $artistId = (int) trim($entry['artist']);
                        }
                    }
                    
                    $query = SetlistSong::where('title', $title);
                    if ($artistId !== null) {
                        $query->where('artist_id', $artistId);
                    } else {
                        $query->whereNull('artist_id');
                    }
                    
                    $song = $query->first();
                    
                    if ($song) {
                        $updatedEntry['song'] = $song->id;
                    }
                }
            }
            
            $updated[] = $updatedEntry;
        }
        
        return $updated;
    }
}

