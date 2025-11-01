<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SongResource\Pages;
use App\Filament\Resources\SongResource\RelationManagers;
use App\Models\Song;
use App\Models\Album;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SongResource extends Resource
{
    protected static ?string $model = Song::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Mr.Children Database';

    protected static ?int $navigationSort = 25;

    protected static ?string $navigationLabel = '楽曲';

    protected static ?string $modelLabel = '楽曲';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('タイトル')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('album_id')
                    ->label('アルバム')
                    ->options(fn() => \App\Models\Album::query()
                        ->whereNotNull('title')
                        ->where('title', '!=', '')
                        ->orderBy('title')
                        ->pluck('title', 'id'))
                    ->searchable()
                    ->native(false)
                    ->nullable(),

                Forms\Components\Select::make('single_id')
                    ->label('シングル')
                    ->options(fn() => \App\Models\Single::query()
                        ->whereNotNull('title')
                        ->where('title', '!=', '')
                        ->orderBy('title')
                        ->pluck('title', 'id'))
                    ->searchable()
                    ->native(false)
                    ->nullable(),
                // 年（year）
                Forms\Components\Select::make('year')
                    ->label('年')
                    ->options(fn() => \App\Models\Song::query()
                        ->distinct()
                        ->whereNotNull('year')
                        ->where('year', '!=', '')
                        ->orderBy('year')
                        ->pluck('year', 'year'))
                    ->searchable()
                    ->native(false)
                    ->placeholder('新しい年を入力する場合は追加')
                    ->reactive()
                    ->createOptionUsing(fn(string $label) => (int) $label)
                    ->required()
                    ->columnSpan(1),

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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable(),
                Tables\Columns\TextColumn::make('album.title')
                    ->label('アルバム名')
                    ->sortable(),
                Tables\Columns\TextColumn::make('single.title')
                    ->label('シングル名')
                    ->sortable(),
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
                Tables\Filters\SelectFilter::make('album_id')
                    ->relationship('album', 'title')
                    ->label('アルバム')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('single_id')
                    ->relationship('single', 'title')
                    ->label('シングル')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListSongs::route('/'),
            'create' => Pages\CreateSong::route('/create'),
            'edit' => Pages\EditSong::route('/{record}/edit'),
        ];
    }
}
