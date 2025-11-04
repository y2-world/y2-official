<?php

namespace App\Filament\Resources\SetlistSongResource\Pages;

use App\Filament\Resources\SetlistSongResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSetlistSongs extends ListRecords
{
    protected static string $resource = SetlistSongResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
