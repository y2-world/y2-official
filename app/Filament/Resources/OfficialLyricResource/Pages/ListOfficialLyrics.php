<?php

namespace App\Filament\Resources\OfficialLyricResource\Pages;

use App\Filament\Resources\OfficialLyricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfficialLyrics extends ListRecords
{
    protected static string $resource = OfficialLyricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
