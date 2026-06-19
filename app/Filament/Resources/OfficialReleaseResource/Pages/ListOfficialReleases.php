<?php

namespace App\Filament\Resources\OfficialReleaseResource\Pages;

use App\Filament\Resources\OfficialReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfficialReleases extends ListRecords
{
    protected static string $resource = OfficialReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
