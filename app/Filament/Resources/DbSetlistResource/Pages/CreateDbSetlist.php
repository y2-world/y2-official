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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = DbSetlistResource::mergeEncoreFormData($data);
        $this->pendingRowTitle = $data['row_title'] ?? null;
        unset($data['row_title']);

        // マイページの人が作るセットリストは、その人のもの
        return $data + \App\Support\AdminData::ownerAttributes('setlist');
    }

    // 「保存して次を作成」の直後だけ、続けて同じアーティストのセットリストを登録しやすいよう
    // _artist_idを引き継ぐ。一覧から改めて新規作成を開いた場合はここを通らないため、
    // 通常通り未選択に戻る。
    public function create(bool $another = false): void
    {
        $artistId = $another ? $this->form->getRawState()['_artist_id'] ?? null : null;

        parent::create($another);

        if ($another && $artistId) {
            $this->form->fill(['_artist_id' => $artistId]);
        }
    }

    protected function afterCreate(): void
    {
        $this->clearDraftAutosave();

        \App\Support\AdminData::setRowTitle($this->record->tour_id, $this->record->row, $this->record->order_no, $this->pendingRowTitle);
    }
}
