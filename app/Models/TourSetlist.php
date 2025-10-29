<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourSetlist extends Model
{
    protected $casts = [
        'setlist' => 'array',
        'encore' => 'array',
    ];

    protected $fillable = [
        'tour_id',
        'order_no',
        'subtitle',
        'setlist',
        'encore',
    ];

    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id', 'id');
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
}
