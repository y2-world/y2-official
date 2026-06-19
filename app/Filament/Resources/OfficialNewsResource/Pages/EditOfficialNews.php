<?php

namespace App\Filament\Resources\OfficialNewsResource\Pages;

use App\Filament\Resources\OfficialNewsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfficialNews extends EditRecord
{
    protected static string $resource = OfficialNewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
