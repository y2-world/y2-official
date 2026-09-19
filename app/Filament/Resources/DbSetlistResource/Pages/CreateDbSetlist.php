<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Resources\DbSetlistResource;
use App\Models\DbSetlistRow;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbSetlist extends CreateRecord
{
    protected static string $resource = DbSetlistResource::class;

    public const SESSION_KEY = 'db_setlist_create_last_artist_id';

    private ?string $pendingRowTitle = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingRowTitle = $data['row_title'] ?? null;
        unset($data['row_title']);

        session()->put(self::SESSION_KEY, $this->form->getRawState()['_artist_id'] ?? null);

        return $data;
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
