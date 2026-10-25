<?php

namespace App\Filament\Resources\ArtistResource\Pages;

use App\Filament\Resources\ArtistResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArtist extends CreateRecord
{
    protected static string $resource = ArtistResource::class;

    // マイページの人が作るアーティストは、その人のもの
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + \App\Support\AdminData::ownerAttributes('artist');
    }
}
