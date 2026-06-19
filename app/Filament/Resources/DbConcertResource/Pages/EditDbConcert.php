<?php

namespace App\Filament\Resources\DbConcertResource\Pages;

use App\Filament\Resources\DbConcertResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDbConcert extends EditRecord
{
    protected static string $resource = DbConcertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
