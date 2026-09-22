<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use Illuminate\Console\Command;

class LinkWindsMegaMixSongs extends Command
{
    protected $signature = 'discography:link-winds-megamix {--dry-run}';

    protected $description = 'Link w-inds. Single Mega-Mix tracklist entries (and the PRIME OF LIFE remix exception) to their existing DbSong records';

    private const ARTIST_ID = 1;

    // exception内のタイトル部分（曲名のみ、括弧の注記を除いたもの）=> 紐付け先の曲タイトル
    private const TITLE_MAP = [
        'Forever Memories' => 'Forever Memories',
        'Feel The Fate' => 'Feel The Fate',
        'Paradox' => 'Paradox',
        'try your emotion' => 'try your emotion',
        'Another Days' => 'Another Days',
        'Because of you' => 'Because of you',
        'NEW PARADISE' => 'NEW PARADISE',
        'SUPER LOVER 〜I need you tonight〜' => 'SUPER LOVER 〜I need you tonight〜',
        'Love is message' => 'Love is message',
        'Long Road' => 'Long Road',
        'Pieces' => 'Pieces',
        'キレイだ' => 'キレイだ',
        '四季' => '四季',
        '夢の場所へ' => '夢の場所へ',
        '変わりゆく空' => '変わりゆく空',
        '十六夜の月' => '十六夜の月',
        '約束のカケラ' => '約束のカケラ',
        'IT’S IN THE STARS' => 'IT’S IN THE STARS',
        'TRIAL' => 'TRIAL',
        'ブギウギ66' => 'ブギウギ66',
        'ハナムケ' => 'ハナムケ',

        // 括弧ではなく全角チルダで囲まれているため、下の接尾辞除去の正規表現にはマッチしない
        'SUPER LOVER〜movin’ pleasure mix〜' => 'SUPER LOVER 〜I need you tonight〜',
    ];

    // アルバムid => [exceptionにあるべき完全一致タイトル => 紐付け先曲タイトル]
    private const TARGET_ALBUMS = [142, 169];

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');

        $songIds = \App\Models\DbSong::where('artist_id', self::ARTIST_ID)
            ->pluck('id', 'title');

        foreach (self::TARGET_ALBUMS as $albumId) {
            $album = DbAlbum::find($albumId);
            if (!$album) {
                $this->error("album not found: {$albumId}");
                continue;
            }

            $tracklist = $album->tracklist ?? [];
            $changed = false;

            foreach ($tracklist as $i => &$track) {
                if (!isset($track['exception']) || isset($track['id'])) {
                    continue;
                }

                $exception = $track['exception'];

                // "曲名 (Single Mega-Mix)" "曲名 (Single Mega-Mix / Radio Edit Version)" のような
                // 接尾辞を除いた曲名部分を取り出してマッチさせる
                $baseTitle = preg_replace('/\s*[（(][^（）()]*mix[^（）()]*[）)]\s*$/ui', '', $exception);
                $baseTitle = trim($baseTitle);

                $targetTitle = self::TITLE_MAP[$baseTitle] ?? null;
                if (!$targetTitle || !isset($songIds[$targetTitle])) {
                    continue;
                }

                $this->line("album {$albumId} [{$i}]: 「{$exception}」 -> id={$songIds[$targetTitle]} ({$targetTitle})");
                $track['id'] = $songIds[$targetTitle];
                $changed = true;
            }
            unset($track);

            if ($changed && !$dryRun) {
                $album->tracklist = $tracklist;
                $album->save();
            }
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Done.');
    }
}
