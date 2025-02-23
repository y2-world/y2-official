<?php

namespace App\Filament\Resources\SetlistResource\Pages;

use App\Filament\Resources\SetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSetlist extends EditRecord
{
    protected static string $resource = SetlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
