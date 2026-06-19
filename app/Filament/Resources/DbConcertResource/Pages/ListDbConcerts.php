<?php

namespace App\Filament\Resources\DbConcertResource\Pages;

use App\Filament\Resources\DbConcertResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDbConcerts extends ListRecords
{
    protected static string $resource = DbConcertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
