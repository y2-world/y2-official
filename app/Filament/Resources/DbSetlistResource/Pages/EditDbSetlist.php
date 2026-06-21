<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Resources\DbSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDbSetlist extends EditRecord
{
    protected static string $resource = DbSetlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\SaveAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
