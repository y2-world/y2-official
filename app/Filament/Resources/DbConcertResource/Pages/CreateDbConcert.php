<?php

namespace App\Filament\Resources\DbConcertResource\Pages;

use App\Filament\Resources\DbConcertResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbConcert extends CreateRecord
{
    protected static string $resource = DbConcertResource::class;

    public const SESSION_KEY = 'db_concert_create_last_artist_id';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        session()->put(self::SESSION_KEY, $data['artist_id'] ?? null);

        return $data;
    }
}
