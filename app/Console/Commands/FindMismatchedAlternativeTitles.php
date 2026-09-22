<?php

namespace App\Console\Commands;

use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Models\SlSetlist;
use App\Models\SlSong;
use App\Support\SongTitleNormalizer;
use Illuminate\Console\Command;

class FindMismatchedAlternativeTitles extends Command
{
    protected $signature = 'discography:find-mismatched-alt-titles';

    protected $description = 'List setlist entries whose alternative_title does not contain the linked song\'s own title (likely stale after a song swap)';

    public function handle(): void
    {
        $this->checkSlSetlists();
        $this->checkDbSetlists();
    }

    private function checkSlSetlists(): void
    {
        $songTitles = SlSong::pluck('title', 'id');
        $count = 0;

        SlSetlist::with('artist')->get(['id', 'artist_id', 'title', 'setlist', 'encore', 'fes_setlist', 'fes_encore'])
            ->each(function ($s) use ($songTitles, &$count) {
                $artist = optional($s->artist)->name ?? '?';

                foreach (['setlist', 'encore', 'fes_setlist', 'fes_encore'] as $field) {
                    $this->scanItems((array) ($s->$field ?? []), function ($item) use ($songTitles, $s, $field, $artist, &$count) {
                        $count += $this->reportIfMismatched(
                            $item,
                            $songTitles,
                            "SlSetlist id={$s->id} title=\"{$s->title}\" artist={$artist} field={$field}"
                        );
                    });
                }
            });

        $this->info("SlSetlist mismatches: {$count}");
    }

    private function checkDbSetlists(): void
    {
        $songTitles = DbSong::pluck('title', 'id');
        $count = 0;

        DbSetlist::with('tour')->get(['id', 'tour_id', 'setlist', 'encore'])
            ->each(function ($s) use ($songTitles, &$count) {
                $tourTitle = optional($s->tour)->title ?? '?';

                foreach (['setlist', 'encore'] as $field) {
                    $this->scanItems((array) ($s->$field ?? []), function ($item) use ($songTitles, $s, $field, $tourTitle, &$count) {
                        $count += $this->reportIfMismatched(
                            $item,
                            $songTitles,
                            "DbSetlist id={$s->id} tour=\"{$tourTitle}\" field={$field}"
                        );
                    });
                }
            });

        $this->info("DbSetlist mismatches: {$count}");
    }

    // setlist/encore配列を走査（fes_setlist/fes_encoreのblock形式でネストしたsongsも展開）
    private function scanItems(array $items, callable $callback): void
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (($item['type'] ?? 'song') === 'block' && isset($item['songs']) && is_array($item['songs'])) {
                foreach ($item['songs'] as $nested) {
                    if (is_array($nested)) {
                        $callback($nested);
                    }
                }
                continue;
            }

            $callback($item);
        }
    }

    // 別表記に、紐づく曲の正規化タイトルが部分文字列としてまったく含まれていなければ「食い違い」とみなす
    private function reportIfMismatched(array $item, $songTitles, string $context): int
    {
        $alt = $item['alternative_title'] ?? null;
        if ($alt === null || $alt === '') {
            return 0;
        }

        $songRef = $item['song'] ?? null;
        if ($songRef === null || !is_numeric($songRef)) {
            return 0;
        }

        $songTitle = $songTitles[(int) $songRef] ?? null;
        if ($songTitle === null) {
            return 0;
        }

        $normalizedAlt = SongTitleNormalizer::normalize($alt);
        $normalizedSong = SongTitleNormalizer::normalize($songTitle);

        if ($normalizedSong !== '' && str_contains($normalizedAlt, $normalizedSong)) {
            return 0;
        }

        $this->line("{$context} song_id={$songRef} song_title=\"{$songTitle}\" alt=\"{$alt}\"");
        return 1;
    }
}
