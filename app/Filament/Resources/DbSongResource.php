<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DbSongResource\Pages;
use App\Filament\Resources\DbSongResource\RelationManagers;
use App\Models\DbSong;
use App\Models\DbAlbum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DbSongResource extends Resource
{
    protected static ?string $model = DbSong::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Database';

    protected static ?int $navigationSort = 25;

    protected static ?string $navigationLabel = '楽曲';

    protected static ?string $modelLabel = '楽曲';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('artist_id')
                    ->label('アーティスト')
                    ->options(fn() => \App\Models\Artist::pluck('name', 'id'))
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->live(),
                Forms\Components\TextInput::make('title')
                    ->label('タイトル')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('text')
                    ->label('説明')
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('artist.name')
                    ->label('アーティスト')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y.m.d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('artist_id')
                    ->label('アーティスト')
                    ->options(fn() => \App\Models\Artist::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
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
            'index' => Pages\ListDbSongs::route('/'),
            'create' => Pages\CreateDbSong::route('/create'),
            'edit' => Pages\EditDbSong::route('/{record}/edit'),
        ];
    }
}
