<?php

namespace App\Filament\Resources\LyricResource\Pages;

use App\Filament\Resources\LyricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLyrics extends ListRecords
{
    protected static string $resource = LyricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
