<?php

namespace App\Filament\Resources\DbConcertResource\Pages;

use App\Filament\Resources\DbConcertResource;
use App\Filament\Resources\DbSetlistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDbConcert extends EditRecord
{
    protected static string $resource = DbConcertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageSetlistPatterns')
                ->label('セットリストを管理')
                ->icon('heroicon-o-queue-list')
                ->url(fn () => DbSetlistResource::tourManagementUrl($this->record->getKey())),
            Actions\Action::make('addSetlistPattern')
                ->label('セットリストパターンを追加')
                ->icon('heroicon-o-plus')
                ->url(fn () => DbSetlistResource::getUrl('create', [
                    'tour_id' => $this->record->getKey(),
                    'return_to_tour' => 1,
                ])),
            Actions\DeleteAction::make(),
        ];
    }
}
