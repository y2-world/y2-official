<?php

namespace App\Filament\Resources\DbSingleResource\Pages;

use App\Filament\Resources\DbSingleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDbSingles extends ListRecords
{
    protected static string $resource = DbSingleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
