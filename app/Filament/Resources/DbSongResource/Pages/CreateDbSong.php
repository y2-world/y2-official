<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbSong extends CreateRecord
{
    protected static string $resource = DbSongResource::class;

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

    // sl_song_idsはdehydrated(false)のためモデルへは自動保存されない（hasMany + multiple()を
    // Filamentのrelationship()保存処理がサポートしていないため）。作成後に手動で同期する。
    protected function afterCreate(): void
    {
        $slSongIds = $this->form->getRawState()['sl_song_ids'] ?? [];
        $this->record->slSongs()->update(['db_song_id' => null]);
        \App\Models\SlSong::whereIn('id', $slSongIds)->update(['db_song_id' => $this->record->id]);
    }
}
