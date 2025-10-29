<?php

namespace App\Filament\Resources\BioResource\Pages;

use App\Filament\Resources\BioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBios extends ListRecords
{
    protected static string $resource = BioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
