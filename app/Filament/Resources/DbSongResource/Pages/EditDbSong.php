<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDbSong extends EditRecord
{
    protected static string $resource = DbSongResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // sl_song_idsはdehydrated(false)のためモデルへは自動保存されない（hasMany + multiple()を
    // Filamentのrelationship()保存処理がサポートしていないため）。保存後に手動で同期する。
    // 選択から外れたSlSongはdb_song_idをnullに戻し、新たに選ばれたものだけこのDbSongのidをセットする。
    protected function afterSave(): void
    {
        $slSongIds = $this->form->getRawState()['sl_song_ids'] ?? [];

        $this->record->slSongs()->whereNotIn('id', $slSongIds)->update(['db_song_id' => null]);
        \App\Models\SlSong::whereIn('id', $slSongIds)->update(['db_song_id' => $this->record->id]);
    }
}
