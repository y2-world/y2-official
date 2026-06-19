<?php

namespace App\Filament\Resources\DbSingleResource\Pages;

use App\Filament\Resources\DbSingleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDbSingle extends EditRecord
{
    protected static string $resource = DbSingleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
