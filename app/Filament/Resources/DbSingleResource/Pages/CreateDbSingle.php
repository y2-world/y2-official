<?php

namespace App\Filament\Resources\DbSingleResource\Pages;

use App\Filament\Resources\DbSingleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDbSingle extends CreateRecord
{
    protected static string $resource = DbSingleResource::class;
}
