<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LyricResource\Pages;
use App\Filament\Resources\LyricResource\RelationManagers;
use App\Models\Lyric;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LyricResource extends Resource
{
    protected static ?string $model = Lyric::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Official';

    protected static ?int $navigationSort = 32;

    protected static ?string $navigationLabel = '歌詞';

    protected static ?string $modelLabel = '歌詞';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('タイトル')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('album_id')
                    ->label('アルバム名')
                    ->options(fn() => \App\Models\Disco::where('type', 1)->orderBy('date', 'asc')->pluck('title', 'id'))
                    ->searchable()
                    ->native(false),
                Forms\Components\Select::make('single_id')
                    ->label('シングル名')
                    ->options(fn() => \App\Models\Disco::where('type', 0)->orderBy('date', 'asc')->pluck('title', 'id'))
                    ->searchable()
                    ->native(false),
                Forms\Components\Textarea::make('lyrics')
                    ->label('歌詞')
                    ->rows(20)
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
                    ->label('アルバム')
                    ->options(fn() => \App\Models\Disco::where('type', 1)->orderBy('date', 'asc')->pluck('title', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('single_id')
                    ->label('シングル')
                    ->options(fn() => \App\Models\Disco::where('type', 0)->orderBy('date', 'asc')->pluck('title', 'id'))
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
            'index' => Pages\ListLyrics::route('/'),
            'create' => Pages\CreateLyric::route('/create'),
            'edit' => Pages\EditLyric::route('/{record}/edit'),
        ];
    }
}
