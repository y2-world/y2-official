<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SlSetlist;
use App\Models\SlSong;
use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSong;

class BackfillDbSetlistAlternativeTitle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setlist:backfill-alternative-title {--dry-run : 変更内容を保存せずに確認だけする} {--skip=* : 手動対応済みなどでスキップするdb_concert_idのリスト}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sl_setlists->alternative_title移行より前にDBへコピー済みだったdb_setlistsに、alternative_titleを事後反映する';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $skipIds = array_map('intval', $this->option('skip'));

        $slSetlists = SlSetlist::whereHas('dbConcert')->get();
        $updatedSetlists = 0;
        $updatedItems = 0;

        foreach ($slSetlists as $sl) {
            $dbConcert = $sl->dbConcert;
            if (!$dbConcert || in_array($dbConcert->id, $skipIds, true)) {
                continue;
            }

            $dbSetlists = DbSetlist::where('tour_id', $dbConcert->id)->get();
            if ($dbSetlists->isEmpty()) {
                continue;
            }

            // sl_song.id -> alternative_title のマップを作る（このsl_setlist内のsetlist/encoreから）
            $altBySlSongId = [];
            foreach (['setlist', 'encore'] as $key) {
                foreach (($sl->$key ?? []) as $item) {
                    if (!empty($item['alternative_title']) && isset($item['song'])) {
                        $altBySlSongId[(string) $item['song']] = $item['alternative_title'];
                    }
                }
            }

            if (empty($altBySlSongId)) {
                continue;
            }

            // sl_song.id -> db_song.id の変換テーブルを作る
            $dbSongIdByAlt = [];
            foreach ($altBySlSongId as $slSongId => $alt) {
                $slSong = SlSong::find($slSongId);
                if (!$slSong) {
                    continue;
                }
                $dbSongId = $slSong->db_song_id;
                if (!$dbSongId) {
                    $dbSong = DbSong::where('artist_id', $sl->artist_id)->where('title', $slSong->title)->first();
                    $dbSongId = $dbSong?->id;
                }
                if ($dbSongId) {
                    $dbSongIdByAlt[(string) $dbSongId] = $alt;
                }
            }

            if (empty($dbSongIdByAlt)) {
                continue;
            }

            foreach ($dbSetlists as $dbSetlist) {
                $changed = false;
                $setlist = $dbSetlist->setlist ?? [];
                $encore = $dbSetlist->encore ?? [];

                foreach ([&$setlist, &$encore] as &$items) {
                    foreach ($items as &$item) {
                        $songId = (string) ($item['song'] ?? '');
                        if ($songId !== '' && empty($item['alternative_title']) && isset($dbSongIdByAlt[$songId])) {
                            $item['alternative_title'] = $dbSongIdByAlt[$songId];
                            $changed = true;
                            $updatedItems++;
                        }
                    }
                    unset($item);
                }
                unset($items);

                if ($changed) {
                    $this->line("db_setlist #{$dbSetlist->id} (tour #{$dbConcert->id} \"{$dbConcert->title}\")");
                    $updatedSetlists++;
                    if (!$dryRun) {
                        $dbSetlist->setlist = $setlist;
                        $dbSetlist->encore = $encore;
                        $dbSetlist->save();
                    }
                }
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '') . "更新db_setlist数: {$updatedSetlists} / 更新item数: {$updatedItems}");

        return Command::SUCCESS;
    }
}
