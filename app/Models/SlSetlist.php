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
