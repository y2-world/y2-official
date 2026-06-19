<?php

namespace App\Filament\Resources\SlSongResource\Pages;

use App\Filament\Resources\SlSongResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSlSong extends EditRecord
{
    protected static string $resource = SlSongResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
