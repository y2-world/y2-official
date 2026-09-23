<?php

namespace App\Console\Commands;

use App\Models\DbSetlist;
use Illuminate\Console\Command;

class FixSetlist34CoverArtistNotation extends Command
{
    protected $signature = 'fix:setlist-34-cover-notation {--dry-run}';

    protected $description = 'Mr.Childrenのセットリストで、"曲名／アーティスト名"や"曲名 [アーティスト名]"の生文字列表記をsong/featuring/featuring_typeに分離する';

    // 全角スラッシュ区切り（"曲名／アーティスト名"）: 桑田佳祐&Mr.Children LIVE UFO95
    // 半角角括弧（"曲名 [アーティスト名]"）: 他の公演でのカバー曲
    private const TARGET_SETLIST_IDS = [34, 98, 99, 100, 101, 102, 103];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // カタカナ・全角表記のアーティスト名は、他のセットリストで既に使われている
        // 英語表記（半角）に合わせる
        $artistNameMap = [
            'ステッペン・ウルフ' => 'Steppenwolf',
            'ボブ・ディラン' => 'Bob Dylan',
            'ピンクフロイド' => 'Pink Floyd',
            'T.レックス' => 'T. Rex',
            'ジェファーソン・エアプレイン' => 'Jefferson Airplane',
            'デビッド・ボウイ' => 'David Bowie',
            'セックスピストルズ' => 'Sex Pistols',
            '桑田佳祐＆Mr.Children' => '桑田佳祐 & Mr.Children',
            'The Rolling Stone' => 'The Rolling Stones',
        ];

        // カタカナ表記の曲名も、原題（英語）に直す
        $titleMap = [
            'クロコダイル　ロック' => 'Crocodile Rock',
            'ブーンブーン' => 'Boom Boom',
            'リアル ミー' => 'The Real Me',
            'ボヘミアン ラプソディー' => 'Bohemian Rhapsody',
            'ライト　マイ　ファイア' => 'Light My Fire',
            'コールド　ターキー' => 'Cold Turkey',
            'ゴッド　セイブ　ザ　クィーン' => 'God Save the Queen',
            'サフラジェット　シティ' => 'Suffragette City',
            'ライク　ア　ローリング　ストーン' => 'Like a Rolling Stone',
            'アクロス　ザ　ユニバース' => 'Across the Universe',
            'サティスファクション' => "(I Can't Get No) Satisfaction",
        ];

        $split = function (array $items) use ($artistNameMap, $titleMap): array {
            return array_map(function ($item) use ($artistNameMap, $titleMap) {
                $song = $item['song'] ?? '';
                if (!is_string($song)) {
                    return $item;
                }

                if (str_contains($song, '／')) {
                    [$title, $artist] = array_map('trim', explode('／', $song, 2));
                } elseif (preg_match('/^(.+?)\s*\[(.+)\]$/u', $song, $m)) {
                    $title = trim($m[1]);
                    $artist = trim($m[2]);
                } else {
                    return $item;
                }

                $item['song'] = $titleMap[$title] ?? $title;
                $item['featuring'] = $artistNameMap[$artist] ?? $artist;
                $item['featuring_type'] = 'artist';
                return $item;
            }, $items);
        };

        foreach (self::TARGET_SETLIST_IDS as $id) {
            $setlist = DbSetlist::find($id);
            if (!$setlist) {
                $this->error("DbSetlist id={$id} not found");
                continue;
            }

            $newSetlist = $split($setlist->setlist ?? []);
            $newEncore = $split($setlist->encore ?? []);

            $this->line("=== setlist id={$id} ===");
            foreach ($newSetlist as $item) {
                if (isset($item['featuring'])) {
                    $this->line($item['song'] . ' / ' . $item['featuring']);
                }
            }
            foreach ($newEncore as $item) {
                if (isset($item['featuring'])) {
                    $this->line('[ENCORE] ' . $item['song'] . ' / ' . $item['featuring']);
                }
            }

            if (!$dryRun) {
                $setlist->setlist = $newSetlist;
                $setlist->encore = $newEncore;
                $setlist->save();
            }
        }

        if ($dryRun) {
            $this->info('Dry run, not saving.');
        } else {
            $this->info('Saved.');
        }

        return 0;
    }
}
