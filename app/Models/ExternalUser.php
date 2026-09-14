<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ExternalUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'bio',
        'avatar_url',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function attendances()
    {
        return $this->hasMany(ExternalUserAttendance::class);
    }

    // Yuki本人（サイト運営者）だけが、Manage画面から公式データベース（db_songs等）を
    // 直接編集できるようにするための判定。
    public function isDatabaseManager(): bool
    {
        return $this->email === 'yuki92496@gmail.com';
    }
}
