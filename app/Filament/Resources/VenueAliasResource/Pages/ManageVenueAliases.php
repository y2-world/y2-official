<?php

namespace App\Filament\Resources\VenueAliasResource\Pages;

use App\Filament\Resources\VenueAliasResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

// 一覧の画面のまま、足す・直す（ダイアログ）
class ManageVenueAliases extends ManageRecords
{
    protected static string $resource = VenueAliasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
