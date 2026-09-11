<?php

namespace App\Filament\Resources\SlSongResource\Pages;

use App\Filament\Resources\SlSongResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSlSong extends CreateRecord
{
    protected static string $resource = SlSongResource::class;

    // 「保存して次を作成」の直後だけ、続けて同じアーティストの曲を登録しやすいようartist_idを引き継ぐ。
    // 一覧から改めて新規作成を開いた場合はここを通らないため、通常通り未選択に戻る。
    public function create(bool $another = false): void
    {
        $artistId = $another ? $this->form->getRawState()['artist_id'] ?? null : null;

        parent::create($another);

        if ($another && $artistId) {
            $this->form->fill(['artist_id' => $artistId]);
        }
    }
}
