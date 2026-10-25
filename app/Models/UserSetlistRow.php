<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// マイページのツアーの段のグループタイトル（公式の DbSetlistRow と同じ作り。ツアーは user_concert_id）
class UserSetlistRow extends Model
{
    protected $table = 'user_setlist_rows';

    protected $fillable = [
        'user_concert_id',
        // 管理画面（公式の DbSetlistRow と同じ画面）で使う別名。中身は user_concert_id
        'tour_id',
        'row',
        'order_no',
        'title',
    ];

    // 管理画面で公式の DbSetlistRow と同じ名前で扱えるようにする別名（tour_id = user_concert_id）
    // 管理画面の編集画面は、モデルの中身（toArray）をフォームに入れるので、別名も入るようにする
    protected $appends = ['tour_id'];

    public const ADMIN_COLUMNS = ['tour_id' => 'user_concert_id'];

    public function tour()
    {
        return $this->belongsTo(UserConcert::class, 'user_concert_id');
    }

    public function getTourIdAttribute()
    {
        return $this->attributes['user_concert_id'] ?? null;
    }

    public function setTourIdAttribute($value): void
    {
        $this->attributes['user_concert_id'] = $value;
    }
}
