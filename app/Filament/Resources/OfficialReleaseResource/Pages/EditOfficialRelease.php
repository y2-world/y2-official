<?php

namespace App\Filament\Resources\OfficialReleaseResource\Pages;

use App\Filament\Resources\OfficialReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfficialRelease extends EditRecord
{
    protected static string $resource = OfficialReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
