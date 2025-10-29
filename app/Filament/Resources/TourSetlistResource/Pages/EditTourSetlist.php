<?php

namespace App\Filament\Resources\TourSetlistResource\Pages;

use App\Filament\Resources\TourSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTourSetlist extends EditRecord
{
    protected static string $resource = TourSetlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
