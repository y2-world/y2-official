<?php

namespace App\Filament\Resources\SlSetlistResource\Pages;

use App\Filament\Resources\SlSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSlSetlists extends ListRecords
{
    protected static string $resource = SlSetlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
