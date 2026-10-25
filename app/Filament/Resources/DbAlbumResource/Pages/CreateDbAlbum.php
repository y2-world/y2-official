<?php

namespace App\Filament\Resources\DbAlbumResource\Pages;

use App\Filament\Resources\DbAlbumResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbAlbum extends CreateRecord
{
    protected static string $resource = DbAlbumResource::class;

    // マイページの人が作るアルバムは、その人のもの
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + \App\Support\AdminData::ownerAttributes('album');
    }
}
