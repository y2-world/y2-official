<?php

namespace App\Filament\Resources\LyricResource\Pages;

use App\Filament\Resources\LyricResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLyric extends EditRecord
{
    protected static string $resource = LyricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
