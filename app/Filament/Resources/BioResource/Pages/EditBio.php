<?php

namespace App\Filament\Resources\BioResource\Pages;

use App\Filament\Resources\BioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBio extends EditRecord
{
    protected static string $resource = BioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
