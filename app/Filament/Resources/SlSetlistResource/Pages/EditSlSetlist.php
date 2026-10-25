<?php

namespace App\Filament\Resources\SlSetlistResource\Pages;

use App\Filament\Concerns\HasDraftAutosave;
use App\Filament\Resources\SlSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSlSetlist extends EditRecord
{
    use HasDraftAutosave;

    protected static string $resource = SlSetlistResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $encore = array_values((array) ($data['encore'] ?? []));
        $data['_encore_groups'] = \App\Support\EncoreFormGroups::fromItems($encore);
        $data['_bulk_encore_items'] = $encore;
        $data['_bulk_encore_uuids'] = array_column($encore, '_uuid');
        $data['_bulk_setlist_uuids'] = array_column((array) ($data['setlist'] ?? []), '_uuid');
        unset($data['encore']);
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return SlSetlistResource::mergeEncoreFromForm($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->clearDraftAutosave();
    }
}
