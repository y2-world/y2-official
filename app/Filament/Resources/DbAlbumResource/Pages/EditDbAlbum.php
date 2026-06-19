<?php

namespace App\Filament\Resources\DbAlbumResource\Pages;

use App\Filament\Resources\DbAlbumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDbAlbum extends EditRecord
{
    protected static string $resource = DbAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
