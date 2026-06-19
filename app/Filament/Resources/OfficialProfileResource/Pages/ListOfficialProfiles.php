<?php

namespace App\Filament\Resources\OfficialProfileResource\Pages;

use App\Filament\Resources\OfficialProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfficialProfiles extends ListRecords
{
    protected static string $resource = OfficialProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
