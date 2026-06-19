<?php

namespace App\Filament\Resources\SlSongResource\Pages;

use App\Filament\Resources\SlSongResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSlSongs extends ListRecords
{
    protected static string $resource = SlSongResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
