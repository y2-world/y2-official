<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SlSetlist;
use App\Models\SlSong;

class MigrateSlSetlistVersionToAlternativeTitle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setlist:migrate-version-to-alternative-title {--dry-run : 変更内容を保存せずに確認だけする}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sl_setlists の version フィールドを、曲名+versionを連結した alternative_title に移行する';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        $setlists = SlSetlist::all();
        $updated = 0;

        foreach ($setlists as $setlist) {
            $hasChanges = false;

            $updatedSetlist = $this->migrateEntries($setlist->setlist ?? [], $setlist->artist_id, $hasChanges);
            $updatedEncore = $this->migrateEntries($setlist->encore ?? [], $setlist->artist_id, $hasChanges);
            $updatedFesSetlist = $this->migrateFesEntries($setlist->fes_setlist ?? [], $hasChanges);
            $updatedFesEncore = $this->migrateFesEntries($setlist->fes_encore ?? [], $hasChanges);

            if (!$hasChanges) {
                continue;
            }

            $updated++;
            $this->line("#{$setlist->id} {$setlist->title}");

            if (!$dryRun) {
                $setlist->setlist = $updatedSetlist;
                $setlist->encore = $updatedEncore;
                $setlist->fes_setlist = $updatedFesSetlist;
                $setlist->fes_encore = $updatedFesEncore;
                $setlist->save();
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '') . "対象セットリスト数: {$updated}");

        return Command::SUCCESS;
    }

    /**
     * 通常の setlist/encore の item 配列を移行する。
     *
     * @param array $entries
     * @param mixed $artistId
     * @param bool $hasChanges
     * @return array
     */
    private function migrateEntries(array $entries, $artistId, bool &$hasChanges): array
    {
        foreach ($entries as &$entry) {
            if (empty($entry['version'])) {
                unset($entry['version']);
                continue;
            }

            $entry['alternative_title'] = $this->buildAlternativeTitle($entry['song'] ?? '', $entry['version'], $artistId);
            unset($entry['version']);
            $hasChanges = true;
        }

        return $entries;
    }

    /**
     * フェス用の item 配列（ブロックの songs を含む）を移行する。
     *
     * @param array $entries
     * @param bool $hasChanges
     * @return array
     */
    private function migrateFesEntries(array $entries, bool &$hasChanges): array
    {
        foreach ($entries as &$entry) {
            $blockArtistId = isset($entry['artist']) && is_numeric($entry['artist']) ? (int) $entry['artist'] : null;

            if (isset($entry['songs']) && is_array($entry['songs'])) {
                $entry['songs'] = $this->migrateEntries($entry['songs'], $blockArtistId, $hasChanges);
                continue;
            }

            if (empty($entry['version'])) {
                unset($entry['version']);
                continue;
            }

            $entry['alternative_title'] = $this->buildAlternativeTitle($entry['song'] ?? '', $entry['version'], $blockArtistId);
            unset($entry['version']);
            $hasChanges = true;
        }

        return $entries;
    }

    /**
     * song（SlSong.id または曲名文字列）+ version から、置き換え表示用の alternative_title を組み立てる。
     */
    private function buildAlternativeTitle($songValue, string $version, $artistId): string
    {
        $title = (string) $songValue;

        if (is_numeric($songValue)) {
            $song = SlSong::find($songValue);
            if ($song) {
                $title = $song->title;
            }
        }

        return trim($title . ' ' . $version);
    }
}
