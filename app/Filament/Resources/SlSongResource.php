<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlSongResource\Pages;
use App\Models\DbSong;
use App\Models\SlSong;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SlSongResource extends Resource
{
    protected static ?string $model = SlSong::class;

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
                    ->live()
                    ->nullable(),

                Forms\Components\Select::make('db_song_id')
                    ->label('database楽曲との紐付け')
                    ->helperText('スタンプ帳でこの曲がライブ演奏済みと判定されるために必要です')
                    ->options(function (Forms\Get $get) {
                        $artistId = $get('artist_id');
                        if (!$artistId) return [];
                        return DbSong::where('artist_id', $artistId)->orderBy('title')->pluck('title', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->nullable(),
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
                Tables\Columns\IconColumn::make('db_song_id')
                    ->label('DB紐付け')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn(SlSong $record) => $record->db_song_id !== null),
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
                Tables\Filters\TernaryFilter::make('db_song_id')
                    ->label('DB紐付け状態')
                    ->nullable()
                    ->trueLabel('紐付け済み')
                    ->falseLabel('未紐付け')
                    ->queries(
                        true: fn($query) => $query->whereNotNull('db_song_id'),
                        false: fn($query) => $query->whereNull('db_song_id'),
                    ),
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
            'index' => Pages\ListSlSongs::route('/'),
            'create' => Pages\CreateSlSong::route('/create'),
            'edit' => Pages\EditSlSong::route('/{record}/edit'),
        ];
    }
}
