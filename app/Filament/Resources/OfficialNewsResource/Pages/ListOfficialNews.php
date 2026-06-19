<?php

namespace App\Filament\Resources\OfficialNewsResource\Pages;

use App\Filament\Resources\OfficialNewsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfficialNews extends ListRecords
{
    protected static string $resource = OfficialNewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
