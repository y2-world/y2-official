<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbSong extends CreateRecord
{
    protected static string $resource = DbSongResource::class;
}
