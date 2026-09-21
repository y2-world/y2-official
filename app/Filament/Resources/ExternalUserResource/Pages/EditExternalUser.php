<?php

namespace App\Filament\Resources\ExternalUserResource\Pages;

use App\Filament\Resources\ExternalUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExternalUser extends EditRecord
{
    protected static string $resource = ExternalUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
