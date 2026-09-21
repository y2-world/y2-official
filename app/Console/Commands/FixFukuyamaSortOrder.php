<?php

namespace App\Console\Commands;

use App\Models\DbSong;
use Illuminate\Console\Command;

class FixFukuyamaSortOrder extends Command
{
    protected $signature = 'discography:fix-fukuyama-sort-order {--dry-run}';

    protected $description = 'Reposition the 9 provided/tie-in songs added out of chronological order into their correct place in sort_order';

    private const ARTIST_ID = 5;

    // 挿入したい曲 => その曲が直前に来るべき既存曲（時系列で先着する側）。
    // 複数曲を同じ基準点の前に挿入する場合は配列の並び順がそのまま挿入順になる。
    private const INSERTIONS = [
        // 168: 何度でも花が咲くように私を生きよう(2015-03) の次、169: I am a HERO の前
        ['before' => 'I am a HERO', 'songs' => ['7月7日']],
        // 172: 1461日(2016-08-05) の前
        ['before' => '1461日', 'songs' => ['好きよ 好きよ 好きよ', 'Soup', '恋の中', 'Dogons']],
        // 179: 心音(2020-11) の前
        ['before' => '心音', 'songs' => ['煌']],
        // 191: 妖(2022-12) の前
        ['before' => '妖', 'songs' => ['ププッとフムッとかいけつダンス']],
        // 192: 想望(2023-12-04) の前と後
        ['before' => '想望', 'songs' => ['炎のファイター 〜Carry on the fighting spirit〜']],
        ['before' => 'ひとみ', 'songs' => ['無礼者たちへ']],
        // 「ガリレオ オリジナル・サウンドトラック」(2007-11-21)収録の2曲。
        // 123: 無敵のキミ(2007-04)の後、124: 想 -new love new world-(2008-10)の前
        ['before' => '想 -new love new world-', 'songs' => ['vs. 〜知覚と快楽の螺旋〜', '覚醒モーメント']],
    ];

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');

        $songs = DbSong::where('artist_id', self::ARTIST_ID)->orderBy('sort_order')->get(['id', 'title']);

        $insertSongTitles = [];
        foreach (self::INSERTIONS as $insertion) {
            foreach ($insertion['songs'] as $t) {
                $insertSongTitles[] = $t;
            }
        }

        // 挿入対象曲を除いた既存順のタイトルリストを作り、そこに指定位置で挿入し直す
        $baseOrder = $songs->pluck('title')->reject(fn($t) => in_array($t, $insertSongTitles, true))->values()->all();

        $newOrder = $baseOrder;
        foreach (self::INSERTIONS as $insertion) {
            $pos = array_search($insertion['before'], $newOrder, true);
            if ($pos === false) {
                $this->error("base song not found: {$insertion['before']}");
                return;
            }
            array_splice($newOrder, $pos, 0, $insertion['songs']);
        }

        if (count($newOrder) !== $songs->count()) {
            $this->error('count mismatch: newOrder=' . count($newOrder) . ' songs=' . $songs->count());
            return;
        }

        $titleToId = $songs->pluck('id', 'title');

        foreach ($newOrder as $index => $title) {
            if (!isset($titleToId[$title])) {
                $this->error("title not found in current songs: {$title}");
                return;
            }
        }

        $this->line('Preview around each insertion point:');
        foreach (self::INSERTIONS as $insertion) {
            $pos = array_search($insertion['before'], $newOrder, true);
            $start = max(0, $pos - count($insertion['songs']) - 1);
            $slice = array_slice($newOrder, $start, count($insertion['songs']) + 3);
            $this->line('  ' . implode(' | ', $slice));
        }

        if ($dryRun) {
            $this->info('Dry run complete.');
            return;
        }

        foreach ($newOrder as $index => $title) {
            DbSong::where('id', $titleToId[$title])->update(['sort_order' => $index + 1]);
        }

        $this->info('Done. Total songs reordered: ' . count($newOrder));
    }
}
