<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Resources\DbSetlistResource;
use App\Filament\Resources\DbConcertResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListDbSetlists extends ListRecords
{
    protected static string $resource = DbSetlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageByArtist')
                ->label('アーティスト別に管理')
                ->icon('heroicon-o-queue-list')
                ->form([
                    Forms\Components\Select::make('artist_id')
                        ->label('アーティスト')
                        ->options(fn () => \App\Support\AdminData::artistOptions())
                        ->required()
                        ->native(false)
                        ->searchable(),
                ])
                ->action(fn (array $data) => redirect(DbConcertResource::getUrl('manage-artist', ['artistId' => $data['artist_id']])))
                ->modalSubmitActionLabel('選択'),
            Actions\CreateAction::make(),
        ];
    }
}
