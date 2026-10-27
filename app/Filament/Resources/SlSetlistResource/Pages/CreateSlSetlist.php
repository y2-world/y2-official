<?php

namespace App\Filament\Resources\SlSetlistResource\Pages;

use App\Filament\Concerns\HasDraftAutosave;
use App\Filament\Resources\SlSetlistResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSlSetlist extends CreateRecord
{
    use HasDraftAutosave;

    protected static string $resource = SlSetlistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SlSetlistResource::applyBulkRegistration($data, $this->form->getRawState());
    }

    protected function afterCreate(): void
    {
        $this->clearDraftAutosave();
    }
}
