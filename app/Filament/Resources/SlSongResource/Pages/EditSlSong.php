<?php

namespace App\Filament\Resources\SlSongResource\Pages;

use App\Filament\Resources\SlSongResource;
use App\Models\SlSong;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSlSong extends EditRecord
{
    protected static string $resource = SlSongResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // DbSong側は同じ曲の複数バージョン（例: 同一曲の異なる年のライブ音源）を1つの
    // database楽曲に束ねる正当なケースがあるため、複数紐付けそのものは禁止しない。
    // ただしタイトルが違う曲を誤って同じdatabase楽曲に紐付けてしまう事故が過去に
    // 実際に起きているため、保存後に気づけるよう警告だけ出す。
    protected function afterSave(): void
    {
        if (!$this->record->db_song_id) {
            return;
        }

        $siblingWithDifferentTitle = SlSong::where('db_song_id', $this->record->db_song_id)
            ->where('id', '!=', $this->record->id)
            ->where('title', '!=', $this->record->title)
            ->exists();

        if ($siblingWithDifferentTitle) {
            Notification::make()
                ->warning()
                ->title('タイトルが異なる曲が同じdatabase楽曲に紐付いています')
                ->body('紐付け先のdatabase楽曲を確認してください。意図しない紐付けの可能性があります。')
                ->persistent()
                ->send();
        }
    }
}
