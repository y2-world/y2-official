<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Concerns\HasDraftAutosave;
use App\Filament\Resources\DbSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbSetlist extends CreateRecord
{
    use HasDraftAutosave;

    protected static string $resource = DbSetlistResource::class;

    private ?string $pendingRowTitle = null;
    public ?int $returnToTourId = null;

    public function mount(): void
    {
        $this->returnToTourId = request()->boolean('return_to_tour') && request()->query('tour_id')
            ? (int) request()->query('tour_id')
            : null;

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = DbSetlistResource::mergeEncoreFormData($data);
        $this->pendingRowTitle = $data['row_title'] ?? null;
        unset($data['row_title']);

        // マイページの人が作るセットリストは、その人のもの
        return $data + \App\Support\AdminData::ownerAttributes('setlist');
    }

    // 「保存して次を作成」の直後だけ、続けて同じツアー・段のパターンを登録しやすいよう
    // 選択内容を引き継ぐ。一覧から改めて新規作成を開いた場合は未選択に戻る。
    public function create(bool $another = false): void
    {
        $state = $another ? $this->form->getRawState() : [];
        $artistId = $state['_artist_id'] ?? null;
        $tourId = $state['tour_id'] ?? null;
        $row = $state['row'] ?? 1;

        parent::create($another);

        if ($another && $artistId && $tourId) {
            $nextOrder = (int) \App\Support\AdminData::query('setlist')->ofTour($tourId)->where('row', $row)->max('order_no') + 1;
            $this->form->fill([
                '_artist_id' => $artistId,
                'tour_id' => $tourId,
                'row' => $row,
                'order_no' => $nextOrder,
            ]);
        }
    }

    protected function afterCreate(): void
    {
        $this->clearDraftAutosave();

        \App\Support\AdminData::setRowTitle($this->record->tour_id, $this->record->row, $this->record->order_no, $this->pendingRowTitle);
    }

    protected function getRedirectUrl(): string
    {
        if ($this->returnToTourId && $this->record) {
            return DbSetlistResource::getUrl('manage-tour', ['tourId' => $this->returnToTourId]);
        }

        return parent::getRedirectUrl();
    }
}
