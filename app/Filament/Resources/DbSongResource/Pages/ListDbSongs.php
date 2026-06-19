<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDbSongs extends ListRecords
{
    protected static string $resource = DbSongResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
