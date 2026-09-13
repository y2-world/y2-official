<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetlist extends Model
{
    protected $casts = [
        'setlist' => 'array',
        'encore' => 'array',
    ];

    protected $fillable = [
        'user_concert_id',
        'order_no',
        'row',
        'subtitle',
        'setlist',
        'encore',
    ];

    public function concert()
    {
        return $this->belongsTo(UserConcert::class, 'user_concert_id');
    }

    public function attendances()
    {
        return $this->hasMany(ExternalUserAttendance::class, 'user_setlist_id');
    }

    // db_setlists同様、setlist/encoreの各アイテムにUUIDを自動付与する
    public function setSetlistAttribute($value)
    {
        $this->attributes['setlist'] = json_encode($this->addUuids($value));
    }

    public function setEncoreAttribute($value)
    {
        $this->attributes['encore'] = json_encode($this->addUuids($value));
    }

    public function getSetlistAttribute($value)
    {
        return $this->decodeWithUuids($value);
    }

    public function getEncoreAttribute($value)
    {
        return $this->decodeWithUuids($value);
    }

    private function addUuids($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        return array_map(function ($item) {
            if (!isset($item['_uuid'])) {
                $item['_uuid'] = \Illuminate\Support\Str::uuid()->toString();
            }
            return $item;
        }, $value);
    }

    private function decodeWithUuids($value)
    {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $decoded = $this->addUuids($decoded);
        }
        return $decoded;
    }
}
