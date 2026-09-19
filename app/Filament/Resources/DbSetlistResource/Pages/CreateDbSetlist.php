<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Resources\DbSetlistResource;
use App\Models\DbSetlistRow;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbSetlist extends CreateRecord
{
    protected static string $resource = DbSetlistResource::class;

    private ?string $pendingRowTitle = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingRowTitle = $data['row_title'] ?? null;
        unset($data['row_title']);

        return $data;
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
        $tourId = $this->record->tour_id;
        $row = $this->record->row;
        $orderNo = $this->record->order_no;
        $title = trim((string) $this->pendingRowTitle);

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
