<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Concerns\HasDraftAutosave;
use App\Filament\Resources\DbSetlistResource;
use App\Models\DbSetlistRow;
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
        $this->pendingRowTitle = $data['row_title'] ?? null;
        unset($data['row_title']);

        $this->previousTourId = $this->record->tour_id;
        $this->previousRow = $this->record->row;
        $this->previousOrderNo = $this->record->order_no;

        return $data;
    }

    protected function afterSave(): void
    {
        $this->clearDraftAutosave();

        $tourId = $this->record->tour_id;
        $row = $this->record->row;
        $orderNo = $this->record->order_no;
        $title = trim((string) $this->pendingRowTitle);

        // tour_id/row/order_noが変わった場合、元のキーに紐づいていたグループタイトルは
        // このパターンに追従させる（別のパターンが同じキーを持つことはないため常に移動でよい）
        $keyChanged = $this->previousTourId !== $tourId
            || $this->previousRow !== $row
            || $this->previousOrderNo !== $orderNo;

        if ($keyChanged) {
            DbSetlistRow::where('tour_id', $this->previousTourId)
                ->where('row', $this->previousRow)
                ->where('order_no', $this->previousOrderNo)
                ->delete();
        }

        if ($title === '') {
            DbSetlistRow::where('tour_id', $tourId)->where('row', $row)->where('order_no', $orderNo)->delete();
            return;
        }

        DbSetlistRow::updateOrCreate(
            ['tour_id' => $tourId, 'row' => $row, 'order_no' => $orderNo],
            ['title' => $title]
        );
    }
}
