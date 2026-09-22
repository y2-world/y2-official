<?php

namespace App\Filament\Resources\SlSetlistResource\Pages;

use App\Filament\Concerns\HasDraftAutosave;
use App\Filament\Resources\SlSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSlSetlist extends CreateRecord
{
    use HasDraftAutosave;

    protected static string $resource = SlSetlistResource::class;

    protected function afterCreate(): void
    {
        $this->clearDraftAutosave();
    }
}
