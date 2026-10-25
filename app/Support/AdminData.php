<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSetlistRow;
use App\Models\DbSong;
use App\Models\DbSingle;
use App\Models\DbAlbum;
use App\Models\SlSong;
use App\Models\UserSingle;
use App\Models\UserAlbum;
use App\Models\UserArtist;
use App\Models\UserConcert;
use App\Models\UserSetlist;
use App\Models\UserSetlistRow;
use App\Models\UserSong;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

// 管理画面（/admin）で読み書きするテーブル。管理者は公式のテーブル、マイページの人はその人のマイページのテーブル。
// 画面（Resource）はここから受け取ったモデル・クエリだけを使い、どちらのテーブルかを気にしない。
// マイページのテーブルの項目名の違い（user_artist_id・user_concert_id）は、マイページのモデルの別名（artist_id・tour_id）で吸収する
class AdminData
{
    private const MODELS = [
        'artist' => [Artist::class, UserArtist::class],
        'concert' => [DbConcert::class, UserConcert::class],
        'setlist' => [DbSetlist::class, UserSetlist::class],
        'song' => [DbSong::class, UserSong::class],
        // 段のグループタイトル
        'setlistRow' => [DbSetlistRow::class, UserSetlistRow::class],
        'single' => [DbSingle::class, UserSingle::class],
        'album' => [DbAlbum::class, UserAlbum::class],
        // Yuki's Setlists の曲との紐付け。公式の曲だけにある
        'slSong' => [SlSong::class, null],
    ];

    // マイページの人として入っているときの、マイページのアカウントの id（管理者なら null）
    public static function memberId(): ?int
    {
        $id = auth()->user()?->external_user_id;

        return $id ? (int) $id : null;
    }

    public static function isMember(): bool
    {
        return self::memberId() !== null;
    }

    public static function model(string $kind): ?string
    {
        return self::MODELS[$kind][self::isMember() ? 1 : 0];
    }

    public static function supports(string $kind): bool
    {
        return self::model($kind) !== null;
    }

    // 読み書きしてよい行だけのクエリ（マイページの人は自分の行だけ）
    public static function query(string $kind): Builder
    {
        $model = self::model($kind);
        $query = $model::query();
        $memberId = self::memberId();
        if ($memberId === null) {
            return $query;
        }

        return match ($kind) {
            'song' => $query->whereIn('user_artist_id', UserArtist::where('external_user_id', $memberId)->select('id')),
            'setlistRow' => $query->whereIn('user_concert_id', UserConcert::where('external_user_id', $memberId)->select('id')),
            default => $query->where('external_user_id', $memberId),
        };
    }

    // 新しく作る行に入れる持ち主
    public static function ownerAttributes(string $kind): array
    {
        $memberId = self::memberId();

        return $memberId === null || in_array($kind, ['song', 'setlistRow'], true) ? [] : ['external_user_id' => $memberId];
    }

    // アーティストの選択肢（公式は公開中のアーティスト）
    public static function artistOptions(): array
    {
        $query = self::query('artist');
        if (!self::isMember()) {
            $query->where('visible', 1);
        }

        return $query->orderBy('id')->pluck('name', 'id')->all();
    }

    public static function table(string $kind): string
    {
        $model = self::model($kind);

        return (new $model)->getTable();
    }

    // 公式の項目名（artist_id・tour_id）を、今のテーブルの本当の項目名にする（マイページは user_artist_id・user_concert_id）。
    // 検証のルールのように、モデルを通さずテーブルを直接見るところで使う
    public static function column(string $kind, string $column): string
    {
        $model = self::model($kind);

        return defined($model . '::ADMIN_COLUMNS') ? ($model::ADMIN_COLUMNS[$column] ?? $column) : $column;
    }

    // パターンの段のグループタイトル（ツアー・段・パターン番号ごと）を入れる。空なら消す
    public static function setRowTitle($tourId, $row, $orderNo, ?string $title): void
    {
        $tourColumn = self::column('setlistRow', 'tour_id');
        $key = [$tourColumn => $tourId, 'row' => $row, 'order_no' => $orderNo];
        $title = trim((string) $title);
        if ($title === '') {
            self::query('setlistRow')->where($key)->delete();

            return;
        }
        $model = self::model('setlistRow');
        $model::updateOrCreate($key, ['title' => $title]);
    }

    public static function rowTitle($tourId, $row, $orderNo): ?string
    {
        return self::query('setlistRow')
            ->where(self::column('setlistRow', 'tour_id'), $tourId)->where('row', $row)->where('order_no', $orderNo)
            ->value('title');
    }

    // 今のテーブルにその項目があるか（マイページのテーブルに無い項目の入力欄は出さない）
    public static function has(string $kind, string $column): bool
    {
        static $cache = [];
        $model = self::model($kind);
        if (!$model) {
            return false;
        }
        $table = (new $model)->getTable();

        return $cache[$table][$column] ??= Schema::hasColumn($table, $column);
    }
}
