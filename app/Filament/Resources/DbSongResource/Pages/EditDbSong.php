<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use App\Models\SlSong;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditDbSong extends EditRecord
{
    protected static string $resource = DbSongResource::class;

    // Filament標準のrelationship()保存はHasMany + multiple()に対応していないため、
    // sl_song_idはモデルのfillableに含めず、この一時プロパティ経由で自前で同期する。
    //
    // 注意: fillForm()（マウント時、GETリクエスト）で取得した値をprotectedプロパティに
    // 保持しておく方式は使えない。Livewireはpublicプロパティのみをリクエスト間で
    // シリアライズ/復元するため、保存アクション（別のPOSTリクエスト）の時点では
    // protectedプロパティはデフォルト値にリセットされてしまう。
    // そのため「フォームを開いた時点の紐付け」の代わりに、保存直前（モデルのsave()より前、
    // 同一リクエスト内）にDBから読み直した値を「操作前の状態」として使う。
    protected int|false|null $slSongIdToSync = false;

    protected ?int $originalSlSongId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->originalSlSongId = $this->record->slSongs()->value('id');

        $this->slSongIdToSync = array_key_exists('sl_song_id', $data)
            ? (int) $data['sl_song_id'] ?: null
            : false;

        // ユーザーが実際に選択を変えようとしている場合のみ、選んだSlSongが既に
        // 「別の」DbSongに紐付いていないかを確認する。ここでサイレントに奪ってしまうと、
        // 奪われた側のDbSongが気づかないまま未紐付けに戻ってしまう事故が起きるため、
        // 保存自体を中断してフォームにエラー表示する。
        if ($this->slSongIdToSync !== false
            && $this->slSongIdToSync !== null
            && $this->slSongIdToSync !== $this->originalSlSongId
        ) {
            $conflicting = SlSong::where('id', $this->slSongIdToSync)
                ->whereNotNull('db_song_id')
                ->where('db_song_id', '!=', $this->record->id)
                ->first();

            if ($conflicting) {
                $conflictingDbSong = $conflicting->dbSong;
                throw ValidationException::withMessages([
                    'data.sl_song_id' => "このセットリスト楽曲「{$conflicting->title}」は既に別の楽曲「"
                        . ($conflictingDbSong?->title ?? '(id: ' . $conflicting->db_song_id . ')')
                        . '」に紐付いています。先にそちらの紐付けを解除してください。',
                ]);
            }
        }

        unset($data['sl_song_id']);

        return $data;
    }

    // ユーザーが選択欄を実際に操作した（保存直前の紐付け=originalSlSongIdと
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
            SlSong::where('id', $currentSlSongId)->update(['db_song_id' => null]);
        }

        if ($this->slSongIdToSync) {
            SlSong::where('id', $this->slSongIdToSync)->update(['db_song_id' => $this->record->id]);
        }
    }
}
