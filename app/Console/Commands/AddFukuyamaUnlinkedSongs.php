<?php

namespace App\Console\Commands;

use App\Models\DbSong;
use App\Models\SlSong;
use Illuminate\Console\Command;

class AddFukuyamaUnlinkedSongs extends Command
{
    protected $signature = 'discography:add-fukuyama-unlinked {--dry-run}';

    protected $description = 'Create DbSong entries for 福山雅治 songs that only exist as SlSong (not in any album/single tracklist), and link them';

    private const ARTIST_ID = 5;

    // それぞれ他アーティストへの提供曲・サントラ参加・CM曲・映画主題歌プロデュース等で、
    // 福山雅治名義のアルバム/シングルには収録されないため、DbSong登録のみ行いtracklistには含めない
    private const SONGS = [
        'Soup',
        'Dogons',
        '無礼者たちへ',
        'ププッとフムッとかいけつダンス',
        '炎のファイター 〜Carry on the fighting spirit〜',
        '煌',
        // 以下3曲は他アーティストへの提供曲だが、AKIRA初回限定盤のボーナスディスク
        // 「Slow Collection」に福山雅治本人による弾き語り/ライブ音源として収録されている。
        // ImportFukuyamaDiscographyがAKIRAのtracklistでこの基本曲名にリンクできるよう、
        // ここで先にDbSongとして作成しておく
        '恋の中',
        '好きよ 好きよ 好きよ',
        '7月7日',
    ];

    public function handle(): void
    {
        $dryRun = (bool) $this->option('dry-run');

        foreach (self::SONGS as $title) {
            $slSong = SlSong::where('artist_id', self::ARTIST_ID)->where('title', $title)->first();
            if (!$slSong) {
                $this->warn("SlSong not found: {$title}");
                continue;
            }
            if ($slSong->db_song_id) {
                $this->line("already linked: {$title} -> db_song_id={$slSong->db_song_id}");
                continue;
            }

            $this->line("=== {$title} ===");
            if ($dryRun) {
                continue;
            }

            $nextSortOrder = (DbSong::where('artist_id', self::ARTIST_ID)->max('sort_order') ?? -1) + 1;
            $dbSong = DbSong::create([
                'artist_id' => self::ARTIST_ID,
                'title' => $title,
                'sort_order' => $nextSortOrder,
            ]);
            $slSong->update(['db_song_id' => $dbSong->id]);
            $this->info("created db_song_id={$dbSong->id}, linked sl_song_id={$slSong->id}");
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Done.');
    }
}
