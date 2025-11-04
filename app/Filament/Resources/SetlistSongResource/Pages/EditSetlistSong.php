<?php

namespace App\Filament\Resources\SetlistSongResource\Pages;

use App\Filament\Resources\SetlistSongResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSetlistSong extends EditRecord
{
    protected static string $resource = SetlistSongResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
