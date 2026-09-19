<?php

namespace App\Filament\Resources\DbConcertResource\Pages;

use App\Filament\Resources\DbConcertResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbConcert extends CreateRecord
{
    protected static string $resource = DbConcertResource::class;

    // 「保存して次を作成」の直後だけ、続けて同じアーティストのツアーを登録しやすいようartist_idを引き継ぐ。
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
