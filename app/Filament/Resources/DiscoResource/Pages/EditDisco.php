<?php

namespace App\Filament\Resources\DiscoResource\Pages;

use App\Filament\Resources\DiscoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDisco extends EditRecord
{
    protected static string $resource = DiscoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
