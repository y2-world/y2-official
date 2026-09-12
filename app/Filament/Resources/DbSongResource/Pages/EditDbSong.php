<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDbSong extends EditRecord
{
    protected static string $resource = DbSongResource::class;

    // Filament標準のrelationship()保存はHasMany + multiple()に対応していないため、
    // sl_song_idはモデルのfillableに含めず、この一時プロパティ経由で自前で同期する。
    // フォームを開いた時点で紐付いていたSlSongのidを保持しておき、保存時の選択値と比較する。
    // これにより「ユーザーが実際に選択欄を操作した場合のみ」変更を反映し、
    // 何も操作していない（＝フォームのhydrationが機能しておらずnullのまま送られてきた）場合に
    // 既存の紐付け（自動紐付け含む）を誤って解除してしまう事故を防ぐ。
    protected int|false|null $slSongIdToSync = false;

    protected ?int $originalSlSongId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function fillForm(): void
    {
        parent::fillForm();

        $this->originalSlSongId = $this->record->slSongs()->value('id');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->slSongIdToSync = array_key_exists('sl_song_id', $data)
            ? $data['sl_song_id']
            : false;

        unset($data['sl_song_id']);

        return $data;
    }

    // ユーザーが選択欄を実際に操作した（フォームを開いた時点の紐付けと異なる値が来た）場合のみ、
    // その差分を反映する。同じ値（=未操作、あるいは操作して元に戻した）ならここでは何もせず、
    // 自動紐付け（DbSong::booted()のsavedイベント）の結果をそのまま残す。
    protected function afterSave(): void
    {
        if ($this->slSongIdToSync === false) {
            return;
        }

        if ($this->slSongIdToSync === $this->originalSlSongId) {
            return;
        }

        if ($this->originalSlSongId) {
            \App\Models\SlSong::where('id', $this->originalSlSongId)->update(['db_song_id' => null]);
        }

        if ($this->slSongIdToSync) {
            \App\Models\SlSong::where('id', $this->slSongIdToSync)->update(['db_song_id' => $this->record->id]);
        }
    }
}
