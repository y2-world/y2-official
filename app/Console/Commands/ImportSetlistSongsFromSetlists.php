<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setlist;
use App\Models\SetlistSong;
use App\Artist;

class ImportSetlistSongsFromSetlists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setlist:import-songs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import songs from setlists JSON data to setlist_songs table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting import of songs from setlists...');
        
        $setlists = Setlist::all();
        $totalSetlists = $setlists->count();
        $processed = 0;
        $created = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar($totalSetlists);
        $bar->start();

        foreach ($setlists as $setlist) {
            try {
                // 単独ライブの setlist
                if (!empty($setlist->setlist) && is_array($setlist->setlist)) {
                    foreach ($setlist->setlist as $entry) {
                        $result = $this->processSongEntry($entry, $setlist->artist_id);
                        if ($result === 'created') $created++;
                        elseif ($result === 'skipped') $skipped++;
                        elseif ($result === 'error') $errors++;
                    }
                }

                // 単独ライブの encore
                if (!empty($setlist->encore) && is_array($setlist->encore)) {
                    foreach ($setlist->encore as $entry) {
                        $result = $this->processSongEntry($entry, $setlist->artist_id);
                        if ($result === 'created') $created++;
                        elseif ($result === 'skipped') $skipped++;
                        elseif ($result === 'error') $errors++;
                    }
                }

                // フェスの fes_setlist
                if (!empty($setlist->fes_setlist) && is_array($setlist->fes_setlist)) {
                    foreach ($setlist->fes_setlist as $entry) {
                        $result = $this->processFesSongEntry($entry);
                        if ($result === 'created') $created++;
                        elseif ($result === 'skipped') $skipped++;
                        elseif ($result === 'error') $errors++;
                    }
                }

                // フェスの fes_encore
                if (!empty($setlist->fes_encore) && is_array($setlist->fes_encore)) {
                    foreach ($setlist->fes_encore as $entry) {
                        $result = $this->processFesSongEntry($entry);
                        if ($result === 'created') $created++;
                        elseif ($result === 'skipped') $skipped++;
                        elseif ($result === 'error') $errors++;
                    }
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
        $this->info("Import completed!");
        $this->info("Processed: {$processed} setlists");
        $this->info("Created: {$created} songs");
        $this->info("Skipped (duplicates): {$skipped} songs");
        $this->info("Errors: {$errors}");

        return Command::SUCCESS;
    }

    /**
     * Process a song entry from regular setlist/encore
     *
     * @param array $entry
     * @param mixed $defaultArtistId
     * @return string
     */
    private function processSongEntry($entry, $defaultArtistId)
    {
        if (!isset($entry['song']) || empty($entry['song'])) {
            return 'skipped';
        }

        $songValue = $entry['song'];
        $title = null;
        $artistId = $defaultArtistId;

        // song が数値 ID の場合
        if (is_numeric($songValue)) {
            $existingSong = SetlistSong::find($songValue);
            if ($existingSong) {
                // 既存の曲なのでスキップ
                return 'skipped';
            }
            // ID が存在しない場合は無視
            return 'skipped';
        }

        // song が文字列（タイトル）の場合
        if (is_string($songValue)) {
            $title = trim($songValue);
            // 例外処理: [xxx] などの記号を除去
            $title = preg_replace('/\s*\[[^\]]+\]/u', '', $title);
            $title = trim($title);
            
            if (empty($title)) {
                return 'skipped';
            }

            // firstOrCreate で重複を防ぐ
            try {
                SetlistSong::firstOrCreate(
                    [
                        'title' => $title,
                        'artist_id' => $artistId,
                    ]
                );
                return 'created';
            } catch (\Exception $e) {
                $this->error("\nError creating song '{$title}': " . $e->getMessage());
                return 'error';
            }
        }

        return 'skipped';
    }

    /**
     * Process a song entry from festival setlist/encore
     *
     * @param array $entry
     * @return string
     */
    private function processFesSongEntry($entry)
    {
        if (!isset($entry['song']) || empty($entry['song'])) {
            return 'skipped';
        }

        $songValue = $entry['song'];
        $artistId = null;

        // JSON の artist フィールドから artist_id を抜き出す
        if (isset($entry['artist']) && !empty($entry['artist'])) {
            // 文字列の ID（例: "32"）または数値の ID（例: 32）を数値に統一
            if (is_numeric($entry['artist'])) {
                $artistId = (int) $entry['artist'];
            } elseif (is_string($entry['artist']) && is_numeric(trim($entry['artist']))) {
                $artistId = (int) trim($entry['artist']);
            }
            
            // 有効な artist_id かチェック（存在するアーティストか）
            if ($artistId !== null) {
                $artistExists = Artist::find($artistId);
                if (!$artistExists) {
                    // アーティストが存在しない場合は null にする
                    $artistId = null;
                }
            }
        }

        $title = null;

        // song が数値 ID の場合
        if (is_numeric($songValue)) {
            $existingSong = SetlistSong::find($songValue);
            if ($existingSong) {
                // 既存の曲なのでスキップ
                return 'skipped';
            }
            // ID が存在しない場合は無視
            return 'skipped';
        }

        // song が文字列（タイトル）の場合
        if (is_string($songValue)) {
            $title = trim($songValue);
            // 例外処理: [xxx] などの記号を除去
            $title = preg_replace('/\s*\[[^\]]+\]/u', '', $title);
            $title = trim($title);
            
            if (empty($title)) {
                return 'skipped';
            }

            // firstOrCreate で重複を防ぐ
            try {
                SetlistSong::firstOrCreate(
                    [
                        'title' => $title,
                        'artist_id' => $artistId,
                    ]
                );
                return 'created';
            } catch (\Exception $e) {
                $this->error("\nError creating song '{$title}': " . $e->getMessage());
                return 'error';
            }
        }

        return 'skipped';
    }
}

