<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Concerns\HasDraftAutosave;
use App\Filament\Resources\DbSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDbSetlist extends EditRecord
{
    use HasDraftAutosave;

    protected static string $resource = DbSetlistResource::class;

    private ?string $pendingRowTitle = null;
    private ?int $previousTourId = null;
    private ?int $previousRow = null;
    private ?int $previousOrderNo = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = DbSetlistResource::mergeEncoreFormData($data);
        $this->pendingRowTitle = $data['row_title'] ?? null;
        unset($data['row_title']);

        $this->previousTourId = $this->record->tour_id;
        $this->previousRow = $this->record->row;
        $this->previousOrderNo = $this->record->order_no;

        return $data;
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        // 登録ボタンはふつう「フォームを送る」ボタン（submit）で、それだと確認のダイアログが出ずにすぐ登録される。
        // 押したら処理を呼ぶボタン（action）にして、合致しない曲があるときだけダイアログを出す
        return parent::getSaveFormAction()
            ->submit(null)
            ->action(fn () => $this->save())
            ->requiresConfirmation(fn (): bool => DbSetlistResource::shouldConfirmUnmatchedBulkSongs($this->form->getRawState()))
            // 見出し・文を設定するとダイアログ付きのボタンになるので、合致しない曲が無いときはダイアログを出さずにそのまま登録する
            ->modalHidden(fn (): bool => !DbSetlistResource::shouldConfirmUnmatchedBulkSongs($this->form->getRawState()))
            ->modalHeading('未一致の楽曲があります')
            ->modalDescription(fn () => DbSetlistResource::unmatchedBulkSongsModalDescription($this->form->getRawState()))
            ->modalSubmitActionLabel('この内容で保存');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return DbSetlistResource::splitEncoreForFormData($data);
    }

    protected function afterSave(): void
    {
        $this->clearDraftAutosave();

        $tourId = $this->record->tour_id;
        $row = $this->record->row;
        $orderNo = $this->record->order_no;

        // tour_id/row/order_noが変わった場合、元のキーに紐づいていたグループタイトルは
        // このパターンに追従させる（別のパターンが同じキーを持つことはないため常に移動でよい）
        $keyChanged = $this->previousTourId !== $tourId
            || $this->previousRow !== $row
            || $this->previousOrderNo !== $orderNo;
        if ($keyChanged) {
            \App\Support\AdminData::setRowTitle($this->previousTourId, $this->previousRow, $this->previousOrderNo, null);
        }

        \App\Support\AdminData::setRowTitle($tourId, $row, $orderNo, $this->pendingRowTitle);
    }
}
