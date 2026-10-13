<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// マイページのスタンプ帳の色とタイトル。ユーザーが自分で決め、全部のスタンプ帳で共通（S・A でも「今日のプレイリスト」でも）
class StampColor extends Model
{
    protected $fillable = ['external_user_id', 'color', 'label', 'sort_order'];
}
