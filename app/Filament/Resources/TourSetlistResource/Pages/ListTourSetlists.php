<?php

namespace App\Filament\Resources\TourSetlistResource\Pages;

use App\Filament\Resources\TourSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTourSetlists extends ListRecords
{
    protected static string $resource = TourSetlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
