<?php

namespace App\Filament\Resources\SlSetlistResource\Pages;

use App\Filament\Resources\SlSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSlSetlist extends EditRecord
{
    protected static string $resource = SlSetlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
