<?php

namespace App\Filament\Resources\DbSingleResource\Pages;

use App\Filament\Resources\DbSingleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbSingle extends CreateRecord
{
    protected static string $resource = DbSingleResource::class;

    // マイページの人が作るシングルは、その人のもの
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + \App\Support\AdminData::ownerAttributes('single');
    }
}
