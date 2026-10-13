<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialNews extends Model
{
    protected $table = 'official_news';

    protected $fillable = ['title', 'visible', 'hidden', 'text', 'date', 'image', 'published_at'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // 本文は管理画面の Markdown エディターで書く（Enter はそのまま1行の改行、空の行で段落）。
    // 以前のエディターで書いた HTML の本文も、Markdown の中の HTML としてそのまま出る
    public function getTextHtmlAttribute(): string
    {
        return trim(\Illuminate\Support\Str::markdown((string) $this->text, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'renderer' => ['soft_break' => "<br>\n"],
        ]));
    }
}
