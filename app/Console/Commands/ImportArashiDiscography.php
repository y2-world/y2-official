<?php

namespace App\Console\Commands;

use App\Models\DbAlbum;
use App\Models\DbSingle;
use App\Models\DbSong;
use Illuminate\Console\Command;

// 嵐のシングル・アルバム・ベスト・EP と、その曲を取り込む。曲（DbSong）はまだ無いので、発売順に作る。
// データは database/data/discography/arashi.json（storage/app/setlist-work/arashi/build_arashi.py が Wikipedia から作ったもの）。
// - CDシングルには通し番号（single_id）、配信シングル・EP には番号なし
// - オリジナル・カラオケ、トーク、リミックス、インストは曲にしない（リンクなしの曲名だけ）。カバー（covered by 嵐）も曲にしない
// - 「: Reborn」などの別バージョンは元の曲につなぎ、曲名を exception に付ける
// - 何度流しても同じになる（曲は曲名、作品は日付と題名で見つけて上書き）
class ImportArashiDiscography extends Command
{
    protected $signature = 'import:arashi-discography {--dry-run : Show what would be created without saving}';

    protected $description = 'Import Arashi singles, albums and songs from database/data/discography/arashi.json';

    private const ARTIST_ID = 20;

    // Yuki のセットリストの曲（SlSong）のうち、曲名の細かい表記が違って自動ではつながらないもの → つなぐ曲。
    // 曲名も Wikipedia の表記（DbSong と同じ）にそろえる
    private const SL_SONG_LINKS = [
        'A･RA･SHI' => 'A・RA・SHI',
        'エナジーソング 〜絶好調超!!!!!〜' => 'エナジーソング 〜絶好調超!!!!〜',
    ];

    private function normalize(string $title): string
    {
        $title = mb_convert_kana($title, 'as');
        $title = str_replace(['’', '‘'], "'", $title);

        return mb_strtolower(preg_replace('/[\s・〜~\-‐]+/u', '', $title), 'UTF-8');
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $data = json_decode(file_get_contents(database_path('data/discography/arashi.json')), true);

        // 曲：発売順に作る（もうある曲はそのまま使う）
        $idByTitle = [];
        foreach (DbSong::where('artist_id', self::ARTIST_ID)->get(['id', 'title']) as $song) {
            $idByTitle[$this->normalize($song->title)] = $song->id;
        }
        // ソロ曲は、曲のアーティスト欄（credit）にメンバー名を入れる（セトリには付けない）
        $credits = [];
        foreach ($data['members'] ?? [] as $title => $member) {
            $credits[$this->normalize($title)] = $member;
        }
        $created = 0;
        foreach ($data['songs'] as $title) {
            $key = $this->normalize($title);
            if (isset($idByTitle[$key])) {
                // 書き方だけ変わった曲（「〜」の前の空白など）は、曲名を新しい書き方にする
                if (!$dryRun) {
                    DbSong::whereKey($idByTitle[$key])->where('title', '!=', $title)->update(['title' => $title]);
                    DbSong::whereKey($idByTitle[$key])->update(['credit' => $credits[$key] ?? null]);
                }
                continue;
            }
            $created++;
            if (!$dryRun) {
                $idByTitle[$key] = DbSong::create(['artist_id' => self::ARTIST_ID, 'title' => $title, 'credit' => $credits[$key] ?? null])->id;
            } else {
                $idByTitle[$key] = 0;
            }
        }
        $this->info("songs: {$created} new, " . count($data['songs']) . ' in data');

        // 曲の並び順は発売順（データの順）。あとから足した曲も、発売順の位置に入るように付け直す
        if (!$dryRun) {
            foreach (array_values($data['songs']) as $i => $title) {
                DbSong::whereKey($idByTitle[$this->normalize($title)])->where('sort_order', '!=', $i)->update(['sort_order' => $i]);
            }
        }

        $counts = ['single' => 0, 'album' => 0];
        foreach ($data['releases'] as $r) {
            $tracks = [];
            foreach ($r['tracks'] as $t) {
                $item = [];
                if ($t['link'] !== null) {
                    $item['id'] = (string) $idByTitle[$this->normalize($t['link'])];
                    if ($t['label'] !== $t['link']) {
                        $item['exception'] = $t['label'];
                    }
                } else {
                    $item['exception'] = $t['label'];
                }
                if (!empty($t['disc'])) {
                    $item['disc'] = $t['disc'];
                }
                $tracks[] = $item;
            }

            $isSingle = in_array($r['kind'], ['single', 'digital_single', 'ep'], true);
            $this->line(sprintf('%-14s %s %s (%d tracks)', $r['kind'], $r['date'], $r['title'], count($tracks)));
            if ($dryRun) {
                continue;
            }
            $key = ['artist_id' => self::ARTIST_ID, 'date' => $r['date'], 'title' => $r['title']];
            if ($isSingle) {
                $saved = DbSingle::updateOrCreate($key, [
                    'single_id' => $r['kind'] === 'single' ? $r['num'] : null,
                    'download' => $r['kind'] !== 'single',
                    'ep' => $r['kind'] === 'ep',
                    'tracklist' => $tracks,
                ]);
                $counts['single']++;
            } else {
                $saved = DbAlbum::updateOrCreate($key, [
                    'album_id' => $r['kind'] === 'album' ? $r['num'] : null,
                    'best' => $r['kind'] === 'best' ? 1 : 0,
                    'mini' => 0,
                    // サウンドトラック・We are ARASHI（メンバーが選んだ曲とトーク）は企画盤
                    'special' => in_array($r['kind'], ['soundtrack', 'digital_album'], true) ? 1 : 0,
                    'tracklist' => $tracks,
                ]);
                $counts['album']++;
            }
            // 決まり11：書き方を直して題名が変わったとき、同じ日の前の書き方の作品が残らないように消す
            $model = $isSingle ? DbSingle::class : DbAlbum::class;
            foreach ($model::where('artist_id', self::ARTIST_ID)->where('date', $r['date'])->whereKeyNot($saved->id)->get() as $old) {
                if ($this->normalize($old->title) === $this->normalize($r['title'])) {
                    $this->line("remove old: {$old->title}");
                    $old->delete();
                }
            }
        }

        foreach (self::SL_SONG_LINKS as $slTitle => $dbTitle) {
            $slSong = \App\Models\SlSong::where('artist_id', self::ARTIST_ID)->where('title', $slTitle)->first();
            $dbId = $idByTitle[$this->normalize($dbTitle)] ?? null;
            if ($slSong && $dbId) {
                $this->line("link: {$slTitle} → {$dbTitle}");
                if (!$dryRun) {
                    $slSong->update(['db_song_id' => $dbId, 'title' => $dbTitle]);
                }
            }
        }

        // つながっている Yuki のセットリストの曲で、曲名が空白などの書き方だけ違うものは、曲の書き方にそろえる
        $dbTitles = DbSong::where('artist_id', self::ARTIST_ID)->pluck('title', 'id');
        foreach (\App\Models\SlSong::where('artist_id', self::ARTIST_ID)->whereNotNull('db_song_id')->get() as $slSong) {
            $dbTitle = $dbTitles[$slSong->db_song_id] ?? null;
            if ($dbTitle !== null && $slSong->title !== $dbTitle && $this->normalize($slSong->title) === $this->normalize($dbTitle)) {
                $this->line("title: {$slSong->title} → {$dbTitle}");
                if (!$dryRun) {
                    $slSong->update(['title' => $dbTitle]);
                }
            }
        }

        $this->info($dryRun ? 'Dry run complete.' : "Import complete: {$counts['single']} singles/EPs, {$counts['album']} albums.");

        return self::SUCCESS;
    }
}
