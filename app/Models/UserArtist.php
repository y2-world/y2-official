<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserArtist extends Model
{
    protected $fillable = [
        'external_user_id',
        'name',
        'setlists_complete',
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
}
