<?php

namespace App\Filament\Resources\DbSetlistResource\Pages;

use App\Filament\Resources\DbSetlistResource;
use App\Filament\Resources\DbConcertResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;

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

    // 一覧の行ごとに、そのセットリストのライブ情報（ツアー・ライブ情報の編集画面）へのボタンを付ける
    public function table(Table $table): Table
    {
        $table = DbSetlistResource::table($table);

        return $table->actions([
            Tables\Actions\Action::make('editTour')
                ->label('ライブ情報')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn ($record) => filled($record->tour_id))
                ->url(fn ($record) => DbConcertResource::getUrl('edit', ['record' => $record->tour_id])),
            ...array_values($table->getActions()),
        ]);
    }
}
