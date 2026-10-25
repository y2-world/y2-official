<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DbAlbumResource\Pages;
use App\Filament\Resources\DbAlbumResource\RelationManagers;
use App\Models\DbAlbum;
use App\Models\DbSong;
use App\Support\AdminData;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DbAlbumResource extends Resource
{
    protected static ?string $model = DbAlbum::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Database';

    protected static ?int $navigationSort = 22;

    protected static ?string $navigationLabel = 'アルバム';

    protected static ?string $modelLabel = 'アルバム';

    // 管理者は公式のアルバム、マイページの人は自分のアルバム（AdminData）
    public static function getModel(): string
    {
        return AdminData::model('album');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return AdminData::query('album');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('artist_id')
                    ->label('アーティスト')
                    ->options(fn() => AdminData::artistOptions())
                    // 選択肢（読み書きしてよいアーティスト）にあるものだけ
                    ->in(fn () => array_keys(AdminData::artistOptions()))
                    ->required()
                    ->native(false)
                    ->searchable(),
                Forms\Components\TextInput::make('album_id')
                    ->label('アルバムID')
                    ->numeric(),
                \App\Filament\Forms\TypedDate::make('date', '発売日')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label('タイトル')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('best')
                    ->label('ベスト')
                    ->onColor('success')
                    ->offColor('gray'),
                Forms\Components\Toggle::make('mini')
                    ->label('ミニアルバム')
                    ->onColor('warning')
                    ->offColor('gray'),
                // ライブ盤・カバー盤など。曲の「収録アルバム」の自動選択では、ベストと同じく選ばない
                Forms\Components\Toggle::make('special')
                    ->label('企画盤')
                    ->onColor('info')
                    ->offColor('gray'),
                Forms\Components\Repeater::make('tracklist')
                    ->label('収録曲')
                    ->schema([
                        // 曲名（メイン）
                        Forms\Components\Select::make('id')
                            ->label('曲名')
                            ->options(fn(Forms\Get $get) => AdminData::query('song')->ofArtists([$get('../../artist_id')])->orderBy('sort_order')->pluck('title', 'id'))
                            ->searchable()
                            ->native(false)
                            ->columnSpanFull(),

                        // アコーディオン（詳細設定）
                        Forms\Components\Section::make('詳細設定')
                            ->collapsible()
                            ->collapsed() // 初期は閉じる
                            ->schema([
                                Forms\Components\TextInput::make('disc')
                                    ->label('ディスク')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('exception')
                                    ->label('例外 (Instrumentalなど)')
                                    ->columnSpan(1),
                            ])
                            ->columns(2), // 横並びにする
                    ])
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->reorderable()
                    ->live()
                    ->itemLabel(function (array $state, string $uuid, $component): string {
                        $items = $component->getState() ?? [];
                        $discTrackCount = [];
                        $globalTrack = 0;
                        $currentDisc = null;

                        foreach ($items as $itemUuid => $item) {
                            // ディスク番号（空なら前の値を引き継ぐ）
                            if (!empty($item['disc'])) {
                                $currentDisc = $item['disc'];
                            }

                            // ディスクが設定されていない → 通常の曲番
                            if (empty($currentDisc)) {
                                $globalTrack++;
                                if ($itemUuid === $uuid) {
                                    return (string)$globalTrack;
                                }
                                continue;
                            }

                            // ディスクごとのカウント
                            if (!isset($discTrackCount[$currentDisc])) {
                                $discTrackCount[$currentDisc] = 1;
                            } else {
                                $discTrackCount[$currentDisc]++;
                            }

                            // 現在行が対象なら返す
                            if ($itemUuid === $uuid) {
                                return "{$currentDisc} - {$discTrackCount[$currentDisc]}";
                            }
                        }

                        return (string) (count($items) ?: 1);
                    }),
                Forms\Components\Textarea::make('text')
                    ->label('テキスト')
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
                Tables\Columns\ToggleColumn::make('mini')
                    ->label('ミニアルバム')
                    ->onColor('warning')
                    ->offColor('gray'),
                Tables\Columns\ToggleColumn::make('special')
                    ->label('企画盤')
                    ->onColor('info')
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
                Tables\Filters\SelectFilter::make('artist_id')
                    ->label('アーティスト')
                    ->options(fn() => AdminData::artistOptions())
                    // マイページのテーブルは項目名が違うので、どちらのモデルにもある ofArtist で絞る
                    ->query(fn ($query, array $data) => filled($data['value']) ? $query->ofArtist($data['value']) : $query)
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('best')
                    ->label('ベストアルバム')
                    ->placeholder('すべて')
                    ->trueLabel('ベストアルバム')
                    ->falseLabel('オリジナルアルバム'),
                Tables\Filters\TernaryFilter::make('special')
                    ->label('企画盤')
                    ->placeholder('すべて')
                    ->trueLabel('企画盤')
                    ->falseLabel('企画盤以外'),
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
            'index' => Pages\ListDbAlbums::route('/'),
            'create' => Pages\CreateDbAlbum::route('/create'),
            'edit' => Pages\EditDbAlbum::route('/{record}/edit'),
        ];
    }
}
