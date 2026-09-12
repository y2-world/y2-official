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

                // DbSongとSlSongは基本1対1（バージョン違いはセットリスト側の別カラムで表現するため、
                // SlSongのタイトルは常にオリジナルタイトルになる）。DbSong::slSongs()自体はhasMany定義だが、
                // ここでは単一選択のみを許可する。Filament標準のrelationship()保存は
                // HasMany + multiple()に対応していないため、素のフィールドとして持たせ、
                // Pages側のmutateFormDataBeforeSave/afterCreateでsl_song_idキーを取り出して同期する。
                Forms\Components\Select::make('sl_song_id')
                    ->label('セットリスト楽曲との紐付け')
                    ->helperText('この楽曲に対応するセットリスト楽曲。タイトル一致で自動的にも紐付きます')
                    ->options(function (Get $get) {
                        $artistId = $get('artist_id');
                        if (!$artistId) {
                            return [];
                        }
                        return \App\Models\SlSong::where('artist_id', $artistId)->orderBy('title')->pluck('title', 'id');
                    })
                    // 編集画面ではEditRecord::fillFormWithDataAndCallHooksが$record->attributesToArray()を
                    // まるごとLivewireのdataにdata_setするため、default()の分岐（$hydratedDefaultStateがnullの
                    // 場合のみ使われる）を通らず、モデル属性に無いsl_song_idは常にnullのまま復元されない。
                    // afterStateHydratedは常に呼ばれるフックなので、ここで確実に現在の紐付けを反映する。
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?DbSong $record) {
                        $component->state($record?->slSongs->first()?->id);
                    })
                    ->dehydrated()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder('選択してください')
                    ->nullable()
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
                Tables\Columns\IconColumn::make('sl_songs_linked')
                    ->label('セットリスト楽曲紐付け')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn (DbSong $record) => $record->slSongs->isNotEmpty()),
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
                Tables\Filters\TernaryFilter::make('sl_songs_linked')
                    ->label('セットリスト楽曲紐付け状態')
                    ->nullable()
                    ->trueLabel('紐付け済み')
                    ->falseLabel('未紐付け')
                    ->queries(
                        true: fn ($query) => $query->whereHas('slSongs'),
                        false: fn ($query) => $query->whereDoesntHave('slSongs'),
                    ),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('slSongs');
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
