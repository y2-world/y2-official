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
        'is_yuki',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_yuki' => 'boolean',
    ];

    public function attendances()
    {
        return $this->hasMany(ExternalUserAttendance::class);
    }
}
