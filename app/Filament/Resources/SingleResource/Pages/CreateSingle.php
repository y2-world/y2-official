<?php

namespace App\Filament\Resources\SingleResource\Pages;

use App\Filament\Resources\SingleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSingle extends CreateRecord
{
    protected static string $resource = SingleResource::class;
}
