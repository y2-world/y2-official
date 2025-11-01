<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlbumResource\Pages;
use App\Filament\Resources\AlbumResource\RelationManagers;
use App\Models\Album;
use App\Models\Song;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AlbumResource extends Resource
{
    protected static ?string $model = Album::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Mr.Children Database';

    protected static ?int $navigationSort = 22;

    protected static ?string $navigationLabel = 'アルバム';

    protected static ?string $modelLabel = 'アルバム';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('album_id')
                    ->label('アルバムID')
                    ->numeric(),
                Forms\Components\DatePicker::make('date')
                    ->label('発売日')
                    ->required()
                    ->native(false)
                    ->displayFormat('Y.m.d'),
                Forms\Components\TextInput::make('title')
                    ->label('タイトル')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('best')
                    ->label('ベスト')
                    ->onColor('success')
                    ->offColor('gray'),
                Forms\Components\Textarea::make('text')
                    ->label('テキスト')
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('tracklist')
                    ->label('収録曲')
                    ->schema([
                        // Disc（短め）
                        Forms\Components\TextInput::make('disc')
                            ->label('Disc')
                            ->columnSpan(1)
                            ->extraAttributes(['style' => 'width: 80px']),

                        // 曲名（中くらい）
                        Forms\Components\Select::make('id')
                            ->label('曲名')
                            ->options(fn() => Song::pluck('title', 'id'))
                            ->searchable()
                            ->native(false)
                            ->columnSpan(2),

                        // 例外（曲名と同じ長さ）
                        Forms\Components\TextInput::make('exception')
                            ->label('例外')
                            ->columnSpan(2),
                    ])
                    ->columns(5) // ← Disc(1) + 曲名(2) + 例外(2)
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->live()
                    ->reorderable()
                    ->itemLabel(function (array $state, $component): string {
                        // UUIDを使わず、配列順で確実に番号を出す
                        $items = array_values($component->getState() ?? []);
                        foreach ($items as $i => $item) {
                            if ($item === $state) {
                                return (string) ($i + 1);
                            }
                        }
                        return (string) (count($items) ?: 1);
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('album_id')
                    ->label('アルバムID')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('発売日')
                    ->date('Y.m.d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('タイトル')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('best')
                    ->label('ベスト')
                    ->onColor('success')
                    ->offColor('gray'),
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
                Tables\Filters\TernaryFilter::make('best')
                    ->label('ベストアルバム')
                    ->placeholder('すべて')
                    ->trueLabel('ベストアルバム')
                    ->falseLabel('オリジナルアルバム'),
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
            'index' => Pages\ListAlbums::route('/'),
            'create' => Pages\CreateAlbum::route('/create'),
            'edit' => Pages\EditAlbum::route('/{record}/edit'),
        ];
    }
}
