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
        'external_user_id',
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

    public function createdByExternalUser()
    {
        return $this->belongsTo(ExternalUser::class, 'external_user_id');
    }

    public function attendances()
    {
        return $this->hasMany(ExternalUserAttendance::class, 'user_setlist_id');
    }

    // 曲目入力（曲名と別表記の組）を、セットリストの曲の配列にする。
    // 曲名あり：その曲（無ければアーティストの曲として登録）。別表記が曲名と違えば alternative_title に持つ
    // 曲名が空で別表記だけ：カバーなど。アーティストの曲として登録せず、公式と同じく曲名の文字列のまま持つ
    // 両方空の行は捨てる
    public static function itemsFromInput(int $userArtistId, array $titles, array $alternativeTitles = []): array
    {
        $items = [];
        $alternativeTitles = array_values($alternativeTitles);
        foreach (array_values($titles) as $i => $title) {
            $title = trim((string) $title);
            $alternativeTitle = trim((string) ($alternativeTitles[$i] ?? ''));
            if ($title !== '') {
                $song = UserSong::firstOrCreateByTitle($userArtistId, $title);
                $item = ['song' => (string) $song->id];
                if ($alternativeTitle !== '' && $alternativeTitle !== $song->title) {
                    $item['alternative_title'] = $alternativeTitle;
                }
                $items[] = $item;
            } elseif ($alternativeTitle !== '') {
                $items[] = ['song' => $alternativeTitle];
            }
        }

        return $items;
    }

    // 曲目入力（本編・アンコールの曲名と別表記）に1曲でも入っているか。0曲のパターンは保存させない
    public static function inputHasSong(array $input): bool
    {
        return collect(['setlist', 'encore', 'setlist_alt', 'encore_alt'])
            ->flatMap(fn ($key) => (array) ($input[$key] ?? []))
            ->contains(fn ($value) => trim((string) $value) !== '');
    }

    // 曲目入力の画面に出す、曲の行ごとの「曲名」と「別表記」（itemsFromInput の逆）
    public static function inputRowsFromItems(array $items, $songTitles): array
    {
        return array_map(function ($item) use ($songTitles) {
            $song = $item['song'] ?? '';
            if (is_numeric($song) && isset($songTitles[(int) $song])) {
                return ['title' => $songTitles[(int) $song], 'alternative_title' => $item['alternative_title'] ?? ''];
            }

            return ['title' => '', 'alternative_title' => ($item['alternative_title'] ?? '') ?: (string) $song];
        }, array_values($items));
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
