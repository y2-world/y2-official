<?php

namespace App\Support;

use App\Models\SlSong;
use Illuminate\Support\Facades\DB;

// 管理画面の「別表記」の入力候補。その曲に Database・Yuki's Setlists のセトリで使った（ライブでやった）別表記だけ
// （マイページの人が入っているときは、マイページのセトリ）。盤にしか無い表記（アルバムver. など）は出さない。
// 記号の違いだけの表記（「’」と「'」、「～」と「〜」など。iPhone と Mac で入力が変わる）は1つにまとめ、よく使われている書き方を出す。
// 並びはよく使われている表記の順。シングル・アルバムの「例外」の欄には、盤の表記だけを出す（discForSong）
class SongVersions
{
    // [db_songs.id => [表記 => 重み]]（1リクエストに1回だけ作る）
    protected static ?array $byDbSong = null;

    // [user_songs.id => [表記 => 重み]]（マイページの人のとき）
    protected static ?array $byUserSong = null;

    // [曲 => [表記 => 0]]。盤の表記だけ（管理者は db_*、マイページの人は user_*）
    protected static array $discOnly = [];

    // [sl_songs.id => [表記 => 回数]]（Database の曲につながっていない Yuki's Setlists の曲用）
    protected static ?array $bySlSong = null;

    public static function sameKey(string $title): string
    {
        return str_replace(['’', '‘', '“', '”', '～'], ["'", "'", '"', '"', '〜'], $title);
    }

    // パターンの編集の曲（管理者は db_songs.id、マイページの人は user_songs.id）
    public static function forSong($songId): array
    {
        if (!is_numeric($songId)) {
            return [];
        }
        if (AdminData::isMember()) {
            static::$byUserSong ??= static::collect([], ['user_setlists']);

            return static::unique(static::$byUserSong[(int) $songId] ?? []);
        }

        return static::forDbSong($songId);
    }

    // シングル・アルバムの「例外」の欄の候補：ほかの盤でその曲に入れた表記（ベスト盤・再収録で同じ表記を入れるとき）
    public static function discForSong($songId): array
    {
        if (!is_numeric($songId)) {
            return [];
        }
        $prefix = AdminData::isMember() ? 'user' : 'db';
        static::$discOnly[$prefix] ??= static::collect(["{$prefix}_albums", "{$prefix}_singles"], []);

        return static::unique(static::$discOnly[$prefix][(int) $songId] ?? []);
    }

    public static function forDbSong($songId): array
    {
        if (!is_numeric($songId)) {
            return [];
        }
        static::load();

        return static::unique(static::$byDbSong[(int) $songId] ?? []);
    }

    // Yuki's Setlists の曲（sl_songs.id）。Database の曲につながっていればその候補、なければ Yuki's Setlists で使った表記
    public static function forSlSong($slSongId): array
    {
        if (!is_numeric($slSongId)) {
            return [];
        }
        $dbSongId = SlSong::whereKey((int) $slSongId)->value('db_song_id');
        if ($dbSongId) {
            return static::forDbSong($dbSongId);
        }
        static::load();

        return static::unique(static::$bySlSong[(int) $slSongId] ?? []);
    }

    protected static function unique(array $weights): array
    {
        arsort($weights);
        $titles = [];
        foreach (array_keys($weights) as $title) {
            $titles[static::sameKey((string) $title)] ??= (string) $title;
        }

        return array_values($titles);
    }

    protected static function add(array &$map, $id, $title, int $weight): void
    {
        $title = trim((string) $title);
        if (is_numeric($id) && $title !== '') {
            $map[(int) $id][$title] = ($map[(int) $id][$title] ?? 0) + $weight;
        }
    }

    protected static function setlistTitles(string $table): array
    {
        return DB::select("select x->>'song' as song, x->>'alternative_title' as title, count(*) as n from {$table} s, jsonb_array_elements(coalesce(s.setlist::jsonb, '[]') || coalesce(s.encore::jsonb, '[]')) x where coalesce(x->>'alternative_title', '') <> '' group by 1, 2");
    }

    // [曲 => [表記 => 重み]]。重みはセトリで使った回数（盤の表記は 0）
    protected static function collect(array $discTables, array $setlistTables): array
    {
        $map = [];
        foreach ($discTables as $table) {
            $rows = DB::select("select x->>'id' as id, x->>'exception' as title from {$table}, jsonb_array_elements(coalesce(tracklist::jsonb, '[]')) x where coalesce(x->>'exception', '') <> ''");
            foreach ($rows as $row) {
                static::add($map, $row->id, $row->title, 0);
            }
        }
        foreach ($setlistTables as $table) {
            foreach (static::setlistTitles($table) as $row) {
                static::add($map, $row->song, $row->title, (int) $row->n);
            }
        }

        return $map;
    }

    protected static function load(): void
    {
        if (static::$byDbSong !== null) {
            return;
        }
        static::$byDbSong = static::collect([], ['db_setlists']);
        static::$bySlSong = [];
        $slToDb = SlSong::whereNotNull('db_song_id')->pluck('db_song_id', 'id');
        foreach (static::setlistTitles('sl_setlists') as $row) {
            $dbSongId = is_numeric($row->song) ? ($slToDb[(int) $row->song] ?? null) : null;
            if ($dbSongId) {
                static::add(static::$byDbSong, $dbSongId, $row->title, (int) $row->n);
            } else {
                static::add(static::$bySlSong, $row->song, $row->title, (int) $row->n);
            }
        }
    }
}
