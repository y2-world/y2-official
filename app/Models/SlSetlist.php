<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlSetlist extends Model
{
    protected $table = 'sl_setlists';

    protected $casts = [
        'setlist' => 'array',
        'encore' => 'array',
        'fes_setlist' => 'array',
        'fes_encore' => 'array',
        'fes_blocks' => 'array',
        'fes_blocks_encore' => 'array',
        'date' => 'date',
        'fes' => 'boolean',
    ];

    protected $dates = ['date'];

    protected $fillable = [
        'artist_id',
        'db_concert_id',
        'title',
        'date',
        'year',
        'venue',
        'setlist',
        'encore',
        'fes',
        'fes_type',
        'fes_setlist',
        'fes_encore',
        'fes_blocks',
        'fes_blocks_encore',
    ];

    protected static function booted()
    {
        // 保存直前に、setlist/encore/fes_setlist/fes_encore内のsong値を検証する。
        // 「388859」のように曲名が数字だけの場合、Filament側のSelect（曲名で選ぶUI）で
        // 何らかの理由により検索欄の生テキストがそのままsongに保存されてしまうことがある。
        // その文字列がたまたま数値形式だと、以降の全ての表示・集計コードは
        // is_numericだけを根拠に「有効なSlSong.idへの参照」とみなしてしまうため、
        // 実在するIDかどうかに関わらずID扱いされ、実在しなければ曲名を解決できず
        // 数字がそのまま表示され続けてしまう。
        // ここでは「数値かどうか」ではなく「実際にSlSongとして存在するidかどうか」を基準にする。
        // 存在しなければ、その値をタイトルとみなしfirstOrCreateで正しい曲に解決してから
        // 保存する（既存の同名曲があればそれを再利用し、無ければ新規作成する）。
        static::saving(function (SlSetlist $setlist) {
            // 登録・更新のとき、database側のツアーとまだ結び付いていなければ、自動で探して結び付ける
            if (!$setlist->fes && !$setlist->db_concert_id) {
                $setlist->db_concert_id = static::findDbConcertId($setlist);
            }

            foreach (['setlist', 'encore'] as $field) {
                $items = $setlist->{$field};
                if (is_array($items)) {
                    $setlist->{$field} = static::resolveSongReferences($items, $setlist->artist_id);
                }
            }

            foreach (['fes_setlist', 'fes_encore'] as $field) {
                $items = $setlist->{$field};
                if (is_array($items)) {
                    $setlist->{$field} = static::resolveFesSongReferences($items);
                }
            }
        });
    }

    // database 側のライブを探す。まずツアー名が同じもの（公演日が期間に入るものを優先、期間外でも1件だけならそれ。延期公演など）、
    // 無ければ公演日が期間（開始日〜終了日）に入るライブが1件だけのときにそれ。決まらなければ null（手で選ぶ）
    private static function findDbConcertId(SlSetlist $setlist): ?int
    {
        if (!$setlist->artist_id) {
            return null;
        }
        $date = empty($setlist->attributes['date']) ? null : substr((string) $setlist->attributes['date'], 0, 10);
        $concerts = DbConcert::where('artist_id', $setlist->artist_id)->get(['id', 'title', 'date1', 'date2']);
        $inRange = fn ($c) => $date && substr((string) $c->date1, 0, 10) <= $date && substr((string) ($c->date2 ?? $c->date1), 0, 10) >= $date;

        $title = static::normalizeTourTitle((string) $setlist->title);
        $sameTitle = $concerts->filter(fn ($c) => static::normalizeTourTitle((string) $c->title) === $title);
        $sameTitleInRange = $sameTitle->filter($inRange);
        if ($sameTitleInRange->count() === 1) {
            return $sameTitleInRange->first()->id;
        }
        if ($sameTitle->count() === 1) {
            return $sameTitle->first()->id;
        }

        $byDate = $concerts->filter($inRange);

        return $byDate->count() === 1 ? $byDate->first()->id : null;
    }

    // 同じツアーかどうかを判定するためのタイトル正規化。SongTitleNormalizer（曲名専用）とは
    // 別に用意する。スマートクォート("")と直引用符("")、波ダッシュ・全角チルダ、
    // 空白の有無といった表記ゆれを吸収し、db_concert_idの紐付け判定にだけ使う。
    public static function normalizeTourTitle(string $title): string
    {
        $title = mb_convert_kana($title, 'as');
        $title = str_replace(["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", "'"], '"', $title);
        $title = preg_replace('/[\x{301C}\x{FF5E}~]/u', '', $title);
        $title = preg_replace('/\s+/u', '', $title);
        return mb_strtolower($title);
    }

    // setlist/encore用: 各要素のsongが実在するSlSong.idでなければ、
    // その値をタイトルとする曲をartist_id配下でfirstOrCreateし、songを正しいidに差し替える。
    private static function resolveSongReferences(array $items, ?int $artistId): array
    {
        if (!$artistId) {
            return $items;
        }

        return array_map(function ($item) use ($artistId) {
            if (isset($item['song']) && $item['song'] !== '' && !SlSong::whereKey($item['song'])->exists()) {
                $song = SlSong::firstOrCreate([
                    'title' => (string) $item['song'],
                    'artist_id' => $artistId,
                ]);
                $item['song'] = $song->id;
            }
            return $item;
        }, $items);
    }

    // fes_setlist/fes_encore用: 通常のinterleaved形式と、アーティストごとにまとめた
    // block形式（songsキーの中に曲が並ぶ）の両方に対応する。block形式ではartist_idが
    // 要素自身のartistキーで指定されているため、そちらを使う。
    private static function resolveFesSongReferences(array $items): array
    {
        return array_map(function ($item) {
            if (($item['type'] ?? 'song') === 'block' && isset($item['songs']) && is_array($item['songs'])) {
                $blockArtistId = isset($item['artist']) ? (int) $item['artist'] : null;
                $item['songs'] = static::resolveSongReferences($item['songs'], $blockArtistId);
                return $item;
            }

            $songArtistId = isset($item['artist']) ? (int) $item['artist'] : null;
            if ($songArtistId && isset($item['song']) && $item['song'] !== '' && !SlSong::whereKey($item['song'])->exists()) {
                $song = SlSong::firstOrCreate([
                    'title' => (string) $item['song'],
                    'artist_id' => $songArtistId,
                ]);
                $item['song'] = $song->id;
            }
            return $item;
        }, $items);
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function dbConcert()
    {
        return $this->belongsTo(DbConcert::class);
    }

    // dateが設定されたら自動的にyearも設定
    public function setDateAttribute($value)
    {
        $this->attributes['date'] = $value;
        if ($value) {
            $this->attributes['year'] = \Carbon\Carbon::parse($value)->year;
        }
    }

    // yearのアクセサ（dateから年を取得）
    public function getYearAttribute($value)
    {
        // year カラムに値があればそれを返す
        if (!empty($value)) {
            return $value;
        }

        // なければdateから計算
        if (!empty($this->attributes['date'])) {
            return \Carbon\Carbon::parse($this->attributes['date'])->year;
        }

        return null;
    }

    // setlistを設定する際に、UUIDが存在しない場合は追加
    public function setSetlistAttribute($value)
    {
        if (is_array($value)) {
            $value = array_map(function ($item) {
                if (!isset($item['_uuid'])) {
                    $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
                }
                return $item;
            }, $value);
        }
        $this->attributes['setlist'] = json_encode($value);
    }

    // encoreを設定する際に、UUIDが存在しない場合は追加
    public function setEncoreAttribute($value)
    {
        if (is_array($value)) {
            $value = array_map(function ($item) {
                if (!isset($item['_uuid'])) {
                    $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
                }
                return $item;
            }, $value);
        }
        $this->attributes['encore'] = json_encode($value);
    }

    // setlistを取得する際に、UUIDが存在しない場合は追加
    public function getSetlistAttribute($value)
    {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $decoded = array_map(function ($item) {
                if (!isset($item['_uuid'])) {
                    $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
                }
                return $item;
            }, $decoded);
        }
        return $decoded;
    }

    // encoreを取得する際に、UUIDが存在しない場合は追加
    public function getEncoreAttribute($value)
    {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $decoded = array_map(function ($item) {
                if (!isset($item['_uuid'])) {
                    $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
                }
                return $item;
            }, $decoded);
        }
        return $decoded;
    }

    // fes_setlistを設定する際に、UUIDが存在しない場合は追加、artistを文字列に統一
    public function setFesSetlistAttribute($value)
    {
        if (is_array($value)) {
            $value = array_map(function ($item) {
                if (!isset($item['_uuid'])) {
                    $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
                }
                if (isset($item['artist'])) {
                    $item['artist'] = (string) $item['artist'];
                }
                return $item;
            }, $value);
        }
        $this->attributes['fes_setlist'] = json_encode($value);
    }

    // fes_encoreを設定する際に、UUIDが存在しない場合は追加、artistを文字列に統一
    public function setFesEncoreAttribute($value)
    {
        if (is_array($value)) {
            $value = array_map(function ($item) {
                if (!isset($item['_uuid'])) {
                    $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
                }
                if (isset($item['artist'])) {
                    $item['artist'] = (string) $item['artist'];
                }
                return $item;
            }, $value);
        }
        $this->attributes['fes_encore'] = json_encode($value);
    }

    // fes_setlistを取得する際に、UUIDが存在しない場合は追加
    public function getFesSetlistAttribute($value)
    {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $decoded = array_map(function ($item) {
                if (!isset($item['_uuid'])) {
                    $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
                }
                return $item;
            }, $decoded);
        }
        return $decoded;
    }

    // fes_encoreを取得する際に、UUIDが存在しない場合は追加
    public function getFesEncoreAttribute($value)
    {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $decoded = array_map(function ($item) {
                if (!isset($item['_uuid'])) {
                    $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
                }
                return $item;
            }, $decoded);
        }
        return $decoded;
    }
}
