<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Resources\DbSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDbSetlists extends ListRecords
{
    protected static string $resource = DbSetlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
