<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setlist extends Model
{
    protected $casts = [
        'setlist' => 'array',
        'encore' => 'array',
        'fes_setlist' => 'array',
        'fes_encore' => 'array',
        'date' => 'date',
        'fes' => 'boolean',
    ];

    protected $dates = ['date'];

    protected $fillable = [
        'artist_id',
        'title',
        'date',
        'year',
        'venue',
        'setlist',
        'encore',
        'fes',
        'fes_setlist',
        'fes_encore',
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
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

    // fes_setlistを設定する際に、UUIDが存在しない場合は追加
    public function setFesSetlistAttribute($value)
    {
        if (is_array($value)) {
            $value = array_map(function ($item) {
                if (!isset($item['_uuid'])) {
                    $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
                }
                return $item;
            }, $value);
        }
        $this->attributes['fes_setlist'] = json_encode($value);
    }

    // fes_encoreを設定する際に、UUIDが存在しない場合は追加
    public function setFesEncoreAttribute($value)
    {
        if (is_array($value)) {
            $value = array_map(function ($item) {
                if (!isset($item['_uuid'])) {
                    $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
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
