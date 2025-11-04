<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SetlistSongResource\Pages;
use App\Models\SetlistSong;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SetlistSongResource extends Resource
{
    protected static ?string $model = SetlistSong::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?string $navigationLabel = 'セットリスト楽曲';

    protected static ?string $modelLabel = 'セットリスト楽曲';

    protected static ?string $navigationGroup = 'Setlists';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('曲名')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('artist_id')
                    ->label('アーティスト')
                    ->relationship('artist', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('曲名')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('artist.name')
                    ->label('アーティスト')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('更新日')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist')
                    ->relationship('artist', 'name')
                    ->label('アーティスト')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSetlistSongs::route('/'),
            'create' => Pages\CreateSetlistSong::route('/create'),
            'edit' => Pages\EditSetlistSong::route('/{record}/edit'),
        ];
    }
}
