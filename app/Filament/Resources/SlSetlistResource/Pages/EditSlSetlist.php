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
