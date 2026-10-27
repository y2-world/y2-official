<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserArtist extends Model
{
    protected $fillable = [
        'external_user_id',
        'name',
        'setlists_complete',
        // 公式のテーブルと同じ項目（管理画面で同じ画面を使うため）
        'visible',
        // ソロプロジェクトのときのバンド（公式と同じ）
        'band_artist_id',
    ];

    protected $casts = [
        // セトリを全部登録した（スタンプ帳に未演奏の背景を出す）
        'setlists_complete' => 'boolean',
    ];

    public function externalUser()
    {
        return $this->belongsTo(ExternalUser::class);
    }

    public function concerts()
    {
        return $this->hasMany(UserConcert::class);
    }

    public function songs()
    {
        return $this->hasMany(UserSong::class);
    }

    // 公開していないアーティスト（公式と同じ「公開」）は、作った本人にだけ見せる
    public function scopeVisibleTo($query, $externalUserId = null)
    {
        $externalUserId ??= \Illuminate\Support\Facades\Auth::guard('external')->id();

        return $query->where(fn ($q) => $q->where('visible', 1)->when($externalUserId, fn ($q2) => $q2->orWhere('external_user_id', $externalUserId)));
    }

    public function isVisibleTo($externalUserId = null): bool
    {
        $externalUserId ??= \Illuminate\Support\Facades\Auth::guard('external')->id();

        // $this->visible は Eloquent のプロパティ（toArray に出す項目の一覧）なので、公開の値は getAttribute で読む
        return (int) $this->getAttribute('visible') === 1 || ($externalUserId && (int) $this->external_user_id === (int) $externalUserId);
    }
}
