<?php

namespace App\Filament\Resources\DbSongResource\Pages;

use App\Filament\Resources\DbSongResource;
use App\Models\Artist;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListDbSongs extends ListRecords
{
    protected static string $resource = DbSongResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageByArtist')
                ->label('アーティスト別に管理')
                ->icon('heroicon-o-queue-list')
                ->form([
                    Forms\Components\Select::make('artist_id')
                        ->label('アーティスト')
                        ->options(fn () => \App\Support\JapaneseNameSorter::sortOptions(Artist::pluck('name', 'id')->all()))
                        ->required()
                        ->native(false)
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    return redirect(DbSongResource::getUrl('manage-artist', ['artistId' => $data['artist_id']]));
                })
                ->modalSubmitActionLabel('選択'),
            Actions\CreateAction::make(),
        ];
    }
}
