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
    // フォームから送られてきた選択値（false=キー自体が無かった、null=未選択）。
    // originalSlSongIdと比較し、「ユーザーが実際に選択欄を操作した場合のみ」変更を反映する。
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
            ? (int) $data['sl_song_id'] ?: null
            : false;

        unset($data['sl_song_id']);

        return $data;
    }

    // ユーザーが選択欄を実際に操作した（フォームを開いた時点の紐付け=originalSlSongIdと
    // 異なる値がフォームから送られてきた）場合のみ、その差分を反映する。
    // 未操作（=フォームの値がoriginalSlSongIdと同じ）なら、ここでは一切何もしない。
    //
    // これが重要な理由: $record->save()の中でDbSong::booted()のsavedイベント（自動紐付け）が
    // 既に発火済みであり、タイトル変更（例: EXIT→Little）によって紐付け先が
    // originalSlSongIdとは別のSlSongに切り替わっている可能性がある。
    // 「ユーザー操作の有無」を見ずに「現在のDB値と違うから」で書き戻すと、
    // 自動紐付けが新しく設定した紐付けを、フォームの古い値で上書きして戻してしまう。
    protected function afterSave(): void
    {
        if ($this->slSongIdToSync === false) {
            return;
        }

        if ($this->slSongIdToSync === $this->originalSlSongId) {
            return;
        }

        $currentSlSongId = $this->record->slSongs()->value('id');

        if ($currentSlSongId) {
            \App\Models\SlSong::where('id', $currentSlSongId)->update(['db_song_id' => null]);
        }

        if ($this->slSongIdToSync) {
            \App\Models\SlSong::where('id', $this->slSongIdToSync)->update(['db_song_id' => $this->record->id]);
        }
    }
}
