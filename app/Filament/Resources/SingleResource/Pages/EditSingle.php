<?php

namespace App\Filament\Resources\SingleResource\Pages;

use App\Filament\Resources\SingleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSingle extends EditRecord
{
    protected static string $resource = SingleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
